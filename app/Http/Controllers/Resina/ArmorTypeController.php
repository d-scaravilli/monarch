<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\ArmorType;
use App\Models\Resina\Project;
use App\Models\Resina\RecipeCategory;
use App\Support\ResinaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * A project's armor types ("Armature" tab), admin only: which recipes
 * paint them and which step-by-step guide explains them.
 */
class ArmorTypeController extends Controller
{
    public function create(Request $request, Project $project): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, new ArmorType(['position' => ($project->armorTypes()->max('position') ?? 0) + 1]));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateArmor($request, $project);

        DB::transaction(function () use ($project, $data) {
            $armor = $project->armorTypes()->create([
                ...$this->attributes($data),
                'slug' => ResinaSlug::unique($project->armorTypes(), $data['title'], 'armatura'),
            ]);
            $armor->recipes()->sync($this->recipePivot($data));
        });

        return redirect()->route('resina.projects.show', [$project, 'armature'])->with('status', 'Armatura aggiunta.');
    }

    public function edit(Request $request, Project $project, ArmorType $armorType): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, $armorType->load('recipes'));
    }

    public function update(Request $request, Project $project, ArmorType $armorType): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateArmor($request, $project);

        DB::transaction(function () use ($armorType, $data) {
            $armorType->update($this->attributes($data));
            $armorType->recipes()->sync($this->recipePivot($data));
        });

        return redirect()->route('resina.projects.show', [$project, 'armature'])->with('status', 'Armatura aggiornata.');
    }

    public function destroy(Request $request, Project $project, ArmorType $armorType): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $armorType->delete();

        return redirect()->route('resina.projects.show', $project->armorTypes()->exists() ? [$project, 'armature'] : [$project])
            ->with('status', "«{$armorType->title}» eliminata.");
    }

    private function form(Project $project, ArmorType $armor): View
    {
        return view('resina.armor-types.form', [
            'project' => $project,
            'armor' => $armor,
            'guides' => $project->guides()->get(),
            'categories' => RecipeCategory::orderBy('position')->with(['recipes' => fn ($q) => $q->catalog()->orderBy('title')])->get(),
            'recipeIds' => old('recipes', $armor->exists ? $armor->recipes->pluck('id')->all() : []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateArmor(Request $request, Project $project): array
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'who' => 'nullable|string|max:255',
            'description' => 'nullable|string|max:2000',
            'position' => 'required|integer|min:1|max:999',
            'guide_id' => ['nullable', 'integer', Rule::exists('resin_guides', 'id')->where('project_id', $project->id)],
            'recipes' => 'nullable|array|max:20',
            'recipes.*' => ['integer', 'distinct', Rule::exists('resin_recipes', 'id')->where('is_inline', 0)],
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'title' => $data['title'],
            'who' => $data['who'] ?? null,
            'description' => $data['description'] ?? null,
            'position' => $data['position'],
            'guide_id' => $data['guide_id'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<int, array{position: int}>
     */
    private function recipePivot(array $data): array
    {
        return collect($data['recipes'] ?? [])->values()->mapWithKeys(fn ($id, int $index) => [(int) $id => ['position' => $index + 1]])->all();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
