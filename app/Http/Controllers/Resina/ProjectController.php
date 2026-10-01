<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\ArmorType;
use App\Models\Resina\Character;
use App\Models\Resina\Guide;
use App\Models\Resina\GuideStep;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeCategory;
use App\Services\Resina\ClientPayload;
use App\Support\ResinaProjectTheme;
use App\Support\ResinaRows;
use App\Support\ResinaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Tabs of a project page, in order; armature and passo only show
     * when the project has armor types or guides.
     */
    public const TABS = [
        'personaggi' => 'Personaggi',
        'armature' => 'Armature',
        'passo' => 'Passo passo',
        'ricette' => 'Ricette del progetto',
        'riferimenti' => 'Riferimenti',
    ];

    public function __construct(private ClientPayload $payload) {}

    public function index(): View
    {
        return view('resina.projects.index', [
            'projects' => Project::withCount(['characters', 'armorTypes'])->orderBy('position')->get(),
        ]);
    }

    public function show(Request $request, Project $project, string $tab = 'personaggi'): View|RedirectResponse
    {
        $project->load(['groups', 'links', 'armorTypes.recipes', 'armorTypes.guide', 'guides.steps.paints', 'characters.group', 'characters.zones', 'characters.versions']);

        $tabs = collect(self::TABS)->reject(fn (string $label, string $key) => ($key === 'armature' && $project->armorTypes->isEmpty())
            || ($key === 'passo' && $project->guides->isEmpty()));

        if (! $tabs->has($tab)) {
            return redirect()->route('resina.projects.show', $project);
        }

        $user = $request->user();
        $characters = $project->characters;

        return view('resina.projects.show', [
            'project' => $project,
            'tab' => $tab,
            'tabs' => $tabs,
            'theme' => ResinaProjectTheme::for($project->theme),
            'isAdmin' => $user->hasRole('admin'),
            'payload' => [
                'paints' => $this->payload->paints($user),
                'brushes' => $this->payload->brushes($user),
                'urls' => $this->payload->urls(),
                'project' => ['slug' => $project->slug, 'defaultBases' => $project->default_bases, 'armorLabel' => $project->armor_label],
                'groups' => $project->groups->map(fn ($group) => ['slug' => $group->slug, 'name' => $group->name])->all(),
                'characters' => $characters->map(fn (Character $character) => [
                    ...$this->payload->character($character),
                    'href' => route('resina.characters.show', [$project, $character]),
                ])->all(),
                'chosenVersions' => $this->payload->chosenVersions($user, $characters),
                'recipes' => $this->payload->recipesById(
                    $characters->flatMap->zones->pluck('recipe_id')
                        ->merge($project->armorTypes->flatMap->recipes->pluck('id'))
                        ->merge($this->projectRecipeIds($project)),
                    $this->payload->virtualRecipeSlugs($characters, $project),
                ),
                'projectRecipeIds' => $this->projectRecipeIds($project),
                'guides' => $project->guides->map(fn (Guide $guide) => [
                    'slug' => $guide->slug,
                    'steps' => $guide->steps->map(fn (GuideStep $step) => $this->payload->guideStep($step))->all(),
                ])->all(),
                'editUrl' => $user->hasRole('admin') ? route('resina.recipes.edit', '__SLUG__') : null,
            ],
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeAdmin($request);

        return $this->form(new Project(['status' => 'anteprima', 'theme' => 't-ss']));
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateProject($request);

        $project = DB::transaction(function () use ($request, $data) {
            $project = Project::create([
                ...$this->attributes($request, $data),
                'slug' => ResinaSlug::unique(Project::query(), $data['name'], 'progetto'),
                'position' => (Project::max('position') ?? 0) + 1,
            ]);
            $this->syncGroupsAndLinks($project, $data);

            return $project;
        });

        return redirect()->route('resina.projects.show', $project)->with('status', 'Progetto creato.');
    }

    public function edit(Request $request, Project $project): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project->load(['groups', 'links']));
    }

    public function update(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateProject($request);

        DB::transaction(function () use ($request, $project, $data) {
            $project->update($this->attributes($request, $data, $project));
            $this->syncGroupsAndLinks($project, $data);
        });

        return redirect()->route('resina.projects.show', $project)->with('status', 'Progetto aggiornato.');
    }

    /**
     * Characters, zones, versions, armor types and guides go with it
     * (database cascades), and so do the users' progress on them.
     */
    public function destroy(Request $request, Project $project): RedirectResponse
    {
        $this->authorizeAdmin($request);

        DB::transaction(function () use ($project) {
            $project->delete();
            Recipe::deleteOrphanInline();
        });

        if ($project->cover_image_path) {
            Storage::disk('public')->delete($project->cover_image_path);
        }

        return redirect()->route('resina.projects.index')->with('status', "Progetto «{$project->name}» eliminato.");
    }

    private function form(Project $project): View
    {
        return view('resina.projects.form', [
            'project' => $project,
            'themes' => ResinaProjectTheme::keys(),
            'baseRecipes' => Recipe::catalog()->whereHas('category', fn ($q) => $q->where('slug', 'basette'))->orderBy('title')->get(),
            'categories' => RecipeCategory::orderBy('position')->with(['recipes' => fn ($q) => $q->catalog()->orderBy('title')])->get(),
            'groups' => old('groups', $project->exists ? $project->groups->map(fn ($g) => ['id' => $g->id, 'name' => $g->name])->all() : []),
            'links' => old('links', $project->exists ? $project->links->map(fn ($l) => ['id' => $l->id, 'title' => $l->title, 'url' => $l->url, 'description' => $l->description])->all() : []),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateProject(Request $request): array
    {
        return $request->validate([
            'name' => 'required|string|max:255',
            'subtitle' => 'nullable|string|max:255',
            'theme' => ['nullable', Rule::in(ResinaProjectTheme::keys())],
            'status' => 'required|in:completo,anteprima',
            'intro' => 'nullable|string|max:2000',
            'armor_label' => 'nullable|string|max:50',
            'default_bases' => 'nullable|array',
            'default_bases.*' => 'string|exists:resin_recipes,slug',
            'extra_recipes' => 'nullable|array',
            'extra_recipes.*' => 'string|exists:resin_recipes,slug',
            'cover_image' => 'nullable|image|max:4096',
            'remove_cover_image' => 'nullable|boolean',
            'groups' => 'nullable|array',
            'groups.*.id' => 'nullable|integer',
            'groups.*.name' => 'required|string|max:100',
            'links' => 'nullable|array',
            'links.*.id' => 'nullable|integer',
            'links.*.title' => 'required|string|max:255',
            'links.*.url' => 'required|url|max:1000',
            'links.*.description' => 'nullable|string|max:255',
        ], [
            'groups.*.name.required' => 'Ogni gruppo ha bisogno di un nome.',
            'links.*.url.url' => 'Ogni riferimento ha bisogno di un indirizzo valido (https://…).',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(Request $request, array $data, ?Project $project = null): array
    {
        $attributes = [
            'name' => $data['name'],
            'subtitle' => $data['subtitle'] ?? null,
            'theme' => $data['theme'] ?? null,
            'status' => $data['status'],
            'intro' => $data['intro'] ?? null,
            'armor_label' => $data['armor_label'] ?? null,
            'default_bases' => array_values($data['default_bases'] ?? []) ?: null,
            'extra_recipes' => array_values($data['extra_recipes'] ?? []) ?: null,
        ];

        if ($request->hasFile('cover_image')) {
            if ($project?->cover_image_path) {
                Storage::disk('public')->delete($project->cover_image_path);
            }
            $attributes['cover_image_path'] = $request->file('cover_image')->store('resina/projects', 'public');
        } elseif ($request->boolean('remove_cover_image') && $project?->cover_image_path) {
            Storage::disk('public')->delete($project->cover_image_path);
            $attributes['cover_image_path'] = null;
        }

        return $attributes;
    }

    /**
     * Groups and links are edited as ordered rows: rows without an id
     * are new, missing rows are deleted (a deleted group leaves its
     * characters without a group).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncGroupsAndLinks(Project $project, array $data): void
    {
        $keptGroups = [];
        foreach (ResinaRows::ordered($data['groups'] ?? []) as $index => $row) {
            $group = isset($row['id']) ? $project->groups()->find($row['id']) : null;
            if ($group) {
                $group->update(['name' => $row['name'], 'position' => $index + 1]);
            } else {
                $group = $project->groups()->create([
                    'name' => $row['name'],
                    'slug' => ResinaSlug::unique($project->groups(), $row['name'], 'gruppo'),
                    'position' => $index + 1,
                ]);
            }
            $keptGroups[] = $group->id;
        }
        $project->groups()->whereNotIn('id', $keptGroups)->delete();

        $project->links()->delete();
        foreach (ResinaRows::ordered($data['links'] ?? []) as $index => $row) {
            $project->links()->create([
                'position' => $index + 1,
                'title' => $row['title'],
                'url' => $row['url'],
                'description' => $row['description'] ?? null,
            ]);
        }
    }

    /**
     * Every recipe the project uses, catalog recipes only, in the order
     * the "Ricette del progetto" tab lists them (the prototype's order):
     * zones of every character and version, armor types, then extras.
     *
     * @return array<int, int>
     */
    private function projectRecipeIds(Project $project): array
    {
        $catalogIds = Recipe::catalog()->pluck('id', 'slug');

        return $project->characters
            ->flatMap(fn (Character $character) => $character->zones->sortBy(fn ($zone) => [$zone->character_version_id ?? 0, $zone->position])->pluck('recipe_id'))
            ->merge($project->armorTypes->flatMap(fn (ArmorType $armor) => $armor->recipes->pluck('id')))
            ->merge(collect($project->extra_recipes ?? [])->map(fn (string $slug) => $catalogIds[$slug] ?? null))
            ->filter(fn ($id) => $id && $catalogIds->contains($id))
            ->unique()
            ->values()
            ->all();
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
