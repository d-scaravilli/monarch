<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\ArmorType;
use App\Models\Resina\Character;
use App\Models\Resina\Paint;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeCategory;
use App\Models\Resina\RecipeStep;
use App\Services\Resina\ClientPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * The Ricettario: everyone reads it, only the admin changes it. Inline
 * recipes (a single zone's own steps) are edited with their zone, never
 * from here.
 */
class RecipeController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $recipes = Recipe::catalog()
            ->with(['category', 'steps.paints'])
            ->get()
            ->sortBy(fn (Recipe $recipe) => [$recipe->category?->position ?? 999, $recipe->id])
            ->values();

        return view('resina.recipes.index', [
            'payload' => [
                'paints' => $this->payload->paints($request->user()),
                'brushes' => $this->payload->brushes($request->user()),
                'recipes' => $recipes->map(fn (Recipe $recipe) => $this->payload->recipe($recipe))->all(),
                'categories' => RecipeCategory::orderBy('position')->get(['slug', 'name'])->all(),
                'urls' => $this->payload->urls(),
                'editUrl' => $request->user()->hasRole('admin') ? route('resina.recipes.edit', '__SLUG__') : null,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return $this->form($request, new Recipe);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateRecipe($request);

        $recipe = DB::transaction(function () use ($data) {
            $recipe = Recipe::create([
                'slug' => $this->uniqueSlug($data['title']),
                'recipe_category_id' => $data['recipe_category_id'],
                'title' => $data['title'],
                'who' => $data['who'] ?? null,
                'tip' => $data['tip'] ?? null,
                'is_inline' => false,
            ]);
            $this->replaceSteps($recipe, $data['steps']);

            return $recipe;
        });

        return redirect()->to(route('resina.recipes.index').'#r-'.$recipe->slug)->with('status', 'Ricetta creata.');
    }

    public function edit(Request $request, Recipe $recipe): View
    {
        $this->authorizeAdmin($request);
        abort_if($recipe->is_inline, 404);

        return $this->form($request, $recipe->load('steps.paints'));
    }

    public function update(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($recipe->is_inline, 404);

        $data = $this->validateRecipe($request);

        DB::transaction(function () use ($recipe, $data) {
            // The slug never changes: zones, armor types and bases point to it.
            $recipe->update([
                'recipe_category_id' => $data['recipe_category_id'],
                'title' => $data['title'],
                'who' => $data['who'] ?? null,
                'tip' => $data['tip'] ?? null,
            ]);
            $this->replaceSteps($recipe, $data['steps']);
        });

        return redirect()->to(route('resina.recipes.index').'#r-'.$recipe->slug)->with('status', 'Ricetta aggiornata.');
    }

    public function destroy(Request $request, Recipe $recipe): RedirectResponse
    {
        $this->authorizeAdmin($request);
        abort_if($recipe->is_inline, 404);

        $usedBy = $this->usages($recipe);
        if ($usedBy !== []) {
            return back()->withErrors(['recipe' => 'La ricetta è ancora usata da: '.implode(', ', $usedBy).'. Toglila da lì prima di eliminarla.']);
        }

        $recipe->delete();

        return redirect()->route('resina.recipes.index')->with('status', 'Ricetta eliminata.');
    }

    private function form(Request $request, Recipe $recipe): View
    {
        $steps = $recipe->exists
            ? $recipe->steps->map(fn (RecipeStep $step) => [
                'role' => $step->role,
                'usage' => $step->usage,
                'optional' => $step->optional,
                'technique' => $step->technique,
                'coverage' => $step->coverage,
                'paints' => $step->paints->map(fn (Paint $paint) => ['paint_id' => $paint->id, 'drops' => $paint->pivot->drops])->values()->all(),
            ])->all()
            : [];

        return view('resina.recipes.form', [
            'recipe' => $recipe,
            'categories' => RecipeCategory::orderBy('position')->get(),
            'payload' => [
                'paints' => $this->payload->paints($request->user()),
                'steps' => old('steps', $steps),
                'techniques' => RecipeStep::TECHNIQUES,
            ],
            'usedBy' => $recipe->exists ? $this->usages($recipe) : [],
        ]);
    }

    /**
     * @return array{title: string, recipe_category_id: int, who: ?string, tip: ?string, steps: array<int, array<string, mixed>>}
     */
    private function validateRecipe(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'recipe_category_id' => 'required|exists:resin_recipe_categories,id',
            'who' => 'nullable|string|max:255',
            'tip' => 'nullable|string|max:2000',
            'steps' => 'required|array|min:1|max:30',
            'steps.*.role' => 'required|string|max:255',
            'steps.*.usage' => 'nullable|string|max:1000',
            'steps.*.optional' => 'boolean',
            'steps.*.technique' => ['nullable', Rule::in(RecipeStep::TECHNIQUES)],
            'steps.*.coverage' => 'nullable|integer|min:1|max:100',
            'steps.*.paints' => 'required|array|min:1|max:6',
            'steps.*.paints.*.paint_id' => 'required|exists:resin_paints,id',
            'steps.*.paints.*.drops' => 'required|integer|min:1|max:40',
        ], [
            'steps.required' => 'Aggiungi almeno un passaggio.',
            'steps.*.role.required' => 'Ogni passaggio ha bisogno di un ruolo (es. Base, Ombra, Luce).',
            'steps.*.paints.required' => 'Ogni passaggio ha bisogno di almeno un colore.',
        ]);
    }

    /**
     * Steps are stored in the order they arrive: that's the painting order.
     *
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function replaceSteps(Recipe $recipe, array $steps): void
    {
        $recipe->steps()->delete();

        foreach (array_values($steps) as $index => $step) {
            $model = $recipe->steps()->create([
                'position' => $index + 1,
                'role' => $step['role'],
                'usage' => $step['usage'] ?? null,
                'optional' => (bool) ($step['optional'] ?? false),
                'technique' => $step['technique'] ?? null,
                'coverage' => $step['coverage'] ?? null,
            ]);

            // The same paint twice in one step adds up its drops.
            $mix = [];
            foreach ($step['paints'] as $paint) {
                $mix[$paint['paint_id']] = ['drops' => ($mix[$paint['paint_id']]['drops'] ?? 0) + (int) $paint['drops']];
            }
            $model->paints()->attach($mix);
        }
    }

    /**
     * Where the recipe is still referenced, in words.
     *
     * @return array<int, string>
     */
    private function usages(Recipe $recipe): array
    {
        $usedBy = [];

        $zoneCharacters = Character::whereHas('zones', fn ($q) => $q->where('recipe_id', $recipe->id))->pluck('name');
        if ($zoneCharacters->isNotEmpty()) {
            $usedBy[] = 'zone di '.$zoneCharacters->unique()->take(5)->implode(', ').($zoneCharacters->count() > 5 ? '…' : '');
        }

        $armors = ArmorType::whereHas('recipes', fn ($q) => $q->whereKey($recipe->id))->pluck('title');
        if ($armors->isNotEmpty()) {
            $usedBy[] = 'armature ('.$armors->implode(', ').')';
        }

        $projects = Project::whereJsonContains('default_bases', $recipe->slug)
            ->orWhereJsonContains('extra_recipes', $recipe->slug)
            ->pluck('name');
        if ($projects->isNotEmpty()) {
            $usedBy[] = 'progetti ('.$projects->implode(', ').')';
        }

        $basedCharacters = Character::whereJsonContains('bases', $recipe->slug)->pluck('name');
        if ($basedCharacters->isNotEmpty()) {
            $usedBy[] = 'basette di '.$basedCharacters->implode(', ');
        }

        return $usedBy;
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'ricetta';
        $slug = $base;

        for ($i = 2; Recipe::where('slug', $slug)->exists(); $i++) {
            $slug = $base.'-'.$i;
        }

        return $slug;
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
