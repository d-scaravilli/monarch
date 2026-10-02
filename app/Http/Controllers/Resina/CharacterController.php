<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeCategory;
use App\Models\Resina\Zone;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\ReferencePhotos;
use App\Services\Resina\ZoneSync;
use App\Support\ResinaProjectTheme;
use App\Support\ResinaSlug;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CharacterController extends Controller
{
    public const TABS = ['panoramica', 'pelle', 'volto', 'vestiti', 'armatura', 'capelli', 'dettagli', 'basetta', 'reference'];

    public function __construct(private ClientPayload $payload, private ZoneSync $zoneSync) {}

    /**
     * The tabbed character sheet. Everything past the first paint is
     * computed by resources/js/resina (guide.js): the page gets the
     * character, all its versions and the recipes they use.
     */
    public function show(Request $request, Project $project, Character $character, string $tab = 'panoramica'): View
    {
        $user = $request->user();
        $character->load(['zones', 'versions', 'group']);

        return view('resina.characters.show', [
            'project' => $project,
            'character' => $character,
            'theme' => ResinaProjectTheme::for($project->theme),
            'isAdmin' => $user->hasRole('admin'),
            'payload' => $this->payload->characterSheet($user, $character, $project, [
                'tab' => in_array($tab, self::TABS, true) ? $tab : 'panoramica',
                'baseUrl' => route('resina.characters.show', [$project, $character]),
                'copyUrl' => route('resina.figures.copy', $character),
            ]),
        ]);
    }

    /**
     * Remembers the version the user paints this character in.
     */
    public function chooseVersion(Request $request, Character $character): JsonResponse
    {
        abort_unless($character->user_id === null || $character->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'version_id' => ['required', 'integer', Rule::exists('resin_character_versions', 'id')->where('character_id', $character->id)],
        ]);

        $user = $request->user();
        DB::transaction(function () use ($user, $character, $data) {
            $user->resinCharacterVersions()->wherePivot('character_id', $character->id)->detach();
            $user->resinCharacterVersions()->attach($data['version_id'], ['character_id' => $character->id]);
        });

        return response()->json(['version_id' => $data['version_id']]);
    }

    public function create(Request $request, Project $project): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, new Character(['project_id' => $project->id]));
    }

    public function store(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateCharacter($request, $project);

        $character = DB::transaction(function () use ($project, $data) {
            $character = $project->characters()->create([
                ...$this->attributes($data),
                'slug' => ResinaSlug::unique($project->characters(), $data['name'], 'personaggio'),
                'source' => 'catalogo',
                'position' => ($project->characters()->max('position') ?? 0) + 1,
            ]);
            $this->zoneSync->sync($character, null, $data['zones'] ?? []);
            // Every catalog character has a version: its photo comes later.
            $character->versions()->create(['slug' => CharacterVersion::SINGLE_SLUG, 'position' => 1, 'label' => 'Unica']);

            return $character;
        });

        return redirect()->route('resina.characters.edit', [$project, $character])->with('status', 'Personaggio creato. Ora puoi aggiungere le versioni.');
    }

    public function edit(Request $request, Project $project, Character $character): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, $character->load(['zones.recipe', 'versions']));
    }

    public function update(Request $request, Project $project, Character $character): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateCharacter($request, $project);

        DB::transaction(function () use ($character, $data) {
            $character->update($this->attributes($data));
            $this->zoneSync->sync($character, null, $data['zones'] ?? []);
        });

        return redirect()->route('resina.characters.show', [$project, $character])->with('status', 'Personaggio aggiornato.');
    }

    /**
     * Zones, versions and every user's progress on it go too.
     */
    public function destroy(Request $request, Project $project, Character $character): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $versions = $character->versions()->get();

        DB::transaction(function () use ($character) {
            $character->delete();
            Recipe::deleteOrphanInline();
        });
        app(ReferencePhotos::class)->deleteFiles($versions);

        return redirect()->route('resina.projects.show', $project)->with('status', "«{$character->name}» eliminato.");
    }

    private function form(Project $project, Character $character): View
    {
        $zones = $character->exists ? $character->zones->whereNull('character_version_id')->sortBy('position')->values() : collect();

        return view('resina.characters.form', [
            'project' => $project,
            'character' => $character,
            'groups' => $project->groups()->get(),
            'baseRecipes' => Recipe::catalog()->whereHas('category', fn ($q) => $q->where('slug', 'basette'))->orderBy('title')->get(),
            'zoneEditor' => $this->zoneEditorPayload(old('zones', $zones->map(fn (Zone $zone) => $this->zoneRow($zone))->all()), $zones),
        ]);
    }

    /**
     * What the zone editor needs: rows, catalog recipes by category, and
     * the inline recipes the current zones already own.
     *
     * @param  array<int, array<string, mixed>>  $rows
     * @param  Collection<int, Zone>  $zones
     * @return array<string, mixed>
     */
    public static function zoneEditorPayload(array $rows, $zones, bool $canEditInline = true): array
    {
        return [
            'rows' => array_values($rows),
            'categories' => RecipeCategory::orderBy('position')
                ->with(['recipes' => fn ($q) => $q->catalog()->orderBy('title')])
                ->get()
                ->map(fn (RecipeCategory $category) => [
                    'name' => $category->name,
                    'recipes' => $category->recipes->map(fn (Recipe $recipe) => ['id' => $recipe->id, 'title' => $recipe->title])->all(),
                ])->all(),
            'inline' => $zones->filter(fn (Zone $zone) => $zone->recipe?->is_inline)
                ->mapWithKeys(fn (Zone $zone) => [$zone->recipe_id => [
                    'title' => 'Passaggi propri della zona',
                    'editUrl' => $canEditInline ? route('resina.recipes.edit', $zone->recipe) : null,
                ]])->all(),
            'tabs' => [
                'pelle' => 'Pelle', 'volto' => 'Occhi e volto', 'vestiti' => 'Tuta e vestiti', 'armatura' => 'Armatura',
                'capelli' => 'Capelli', 'dettagli' => 'Dettagli', 'basetta' => 'Basetta',
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function zoneRow(Zone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'recipe_id' => $zone->recipe_id,
            'target_hex' => $zone->target_hex,
            'tab' => $zone->tab,
            'note' => $zone->note,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function zoneRules(): array
    {
        return [
            'zones' => 'nullable|array|max:60',
            'zones.*.id' => 'nullable|integer',
            'zones.*.name' => 'required|string|max:100',
            'zones.*.recipe_id' => 'nullable|integer|required_without:zones.*.target_hex',
            'zones.*.target_hex' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'zones.*.tab' => ['nullable', Rule::in(Zone::TABS)],
            'zones.*.note' => 'nullable|string|max:1000',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function zoneMessages(): array
    {
        return [
            'zones.*.name.required' => 'Ogni zona ha bisogno di un nome.',
            'zones.*.recipe_id.required_without' => 'Ogni zona ha bisogno di una ricetta oppure di un colore.',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validateCharacter(Request $request, Project $project): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'alias_it' => 'nullable|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'character_group_id' => ['nullable', 'integer', Rule::exists('resin_character_groups', 'id')->where('project_id', $project->id)],
            'search_query' => 'nullable|string|max:255',
            'versions_note' => 'nullable|string|max:2000',
            'no_face' => 'boolean',
            'no_eyes' => 'boolean',
            'bases' => 'nullable|array',
            'bases.*' => 'string|exists:resin_recipes,slug',
            'tips' => 'nullable|string|max:5000',
            ...self::zoneRules(),
        ], self::zoneMessages());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'alias_it' => $data['alias_it'] ?? null,
            'subtitle' => $data['subtitle'] ?? null,
            'character_group_id' => $data['character_group_id'] ?? null,
            'search_query' => $data['search_query'] ?? null,
            'versions_note' => $data['versions_note'] ?? null,
            'no_face' => (bool) ($data['no_face'] ?? false),
            'no_eyes' => (bool) ($data['no_eyes'] ?? false),
            // No bases ticked: use the project's.
            'bases' => array_values($data['bases'] ?? []) ?: null,
            // One tip per line.
            'tips' => collect(preg_split('/\R/', $data['tips'] ?? ''))->map(fn (string $tip) => trim($tip))->filter()->values()->all(),
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
