<?php

namespace App\Services\Resina;

use App\Models\Resina\ArmorType;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterGroup;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Guide;
use App\Models\Resina\Paint;
use App\Models\Resina\PathStep;
use App\Models\Resina\Project;
use App\Models\Resina\ProjectLink;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeCategory;
use App\Models\Resina\ShopSuggestion;
use App\Models\Resina\Tutorial;
use App\Models\Resina\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

/**
 * Loads the "3D - Resina" shared catalog from database/data/resina/*.json.
 *
 * Idempotent: every row is matched on its natural key (code, slug, or
 * parent + position) and updated in place, so running it again never
 * duplicates anything. It does overwrite whatever was changed from the
 * UI on the imported rows, and a recipe's or guide's steps are replaced
 * as a whole. Rows added from the UI that the JSON doesn't know about
 * are left alone. Personal data (inventories, progress, figures) is
 * never touched.
 */
class CatalogImporter
{
    /**
     * The prototype hard-codes these on the Saint Seiya "Ricette del
     * progetto" tab; the JSON doesn't carry them.
     *
     * @var array<string, array<int, string>>
     */
    private const EXTRA_PROJECT_RECIPES = [
        'saint-seiya' => ['fx-cosmo', 'fx-fire', 'base-rock', 'base-marble', 'base-lava'],
    ];

    /**
     * First segment of a prototype hash link ("#/tecniche/t-prep") →
     * the module route it now points to.
     *
     * @var array<string, string>
     */
    private const PATH_LINK_ROUTES = [
        'tecniche' => 'resina.techniques.index',
        'pennelli' => 'resina.brushes.index',
        'ricette' => 'resina.recipes.index',
        'colori' => 'resina.paints.index',
        'progetti' => 'resina.projects.index',
        'trova' => 'resina.finder',
        'mixer' => 'resina.mixer',
    ];

    /** @var array<string, int> paint code → id */
    private array $paintIds = [];

    /** @var array<string, int> recipe slug → id */
    private array $recipeIds = [];

    /** @var array<string, int> */
    private array $counts = [];

    public function __construct(private ?string $dataPath = null)
    {
        $this->dataPath ??= database_path('data/resina');
    }

    /**
     * @return array<string, int> how many rows of each kind were written
     */
    public function import(): array
    {
        $this->counts = [];

        DB::transaction(function () {
            $this->importPaints();
            $this->importShopSuggestions();
            $this->importRecipeCategories();
            $this->importRecipes();
            $this->importProjects();
            $this->importPath();
            $this->importTutorials();
        });

        return $this->counts;
    }

    private function importPaints(): void
    {
        foreach ($this->read('paints.json') as $index => $paint) {
            $model = Paint::updateOrCreate(['code' => $paint['code']], [
                'name' => $paint['name'],
                'name_en' => $paint['name_en'] ?? null,
                'hex' => strtolower($paint['hex']),
                'line' => $paint['line'] ?? null,
                'type' => $paint['type'],
                'usage' => $paint['usage'] ?? null,
                'position' => $index + 1,
            ]);

            $this->paintIds[$model->code] = $model->id;
            $this->count('colori');
        }
    }

    private function importShopSuggestions(): void
    {
        foreach ($this->read('shop_suggestions.json') as $index => $suggestion) {
            ShopSuggestion::updateOrCreate(['code' => $suggestion['code']], [
                'name' => $suggestion['it'],
                'hex' => strtolower($suggestion['hex']),
                'why' => $suggestion['why'] ?? null,
                'position' => $index + 1,
            ]);
            $this->count('colori da comprare');
        }
    }

    private function importRecipeCategories(): void
    {
        foreach ($this->read('recipe_categories.json') as $index => $category) {
            RecipeCategory::updateOrCreate(['slug' => $category['slug']], [
                'name' => $category['name'],
                'position' => $index + 1,
            ]);
            $this->count('categorie di ricette');
        }
    }

    private function importRecipes(): void
    {
        $categoryIds = RecipeCategory::pluck('id', 'slug');

        foreach ($this->read('recipes.json') as $recipe) {
            $categoryId = $categoryIds[$recipe['category']]
                ?? throw new RuntimeException("Categoria sconosciuta \"{$recipe['category']}\" nella ricetta {$recipe['slug']}.");

            $model = Recipe::updateOrCreate(['slug' => $recipe['slug']], [
                'recipe_category_id' => $categoryId,
                'title' => $recipe['title'],
                'who' => $recipe['who'] ?? null,
                'tip' => $recipe['tip'] ?? null,
                'is_inline' => false,
            ]);

            $this->replaceRecipeSteps($model, $recipe['steps']);
            $this->recipeIds[$model->slug] = $model->id;
            $this->count('ricette');
        }
    }

    /**
     * @param  array<int, array<string, mixed>>  $steps
     */
    private function replaceRecipeSteps(Recipe $recipe, array $steps): void
    {
        // Cascades to the step → paint rows.
        $recipe->steps()->delete();

        foreach ($steps as $step) {
            $model = $recipe->steps()->create([
                'position' => $step['position'],
                'role' => $step['role'],
                'usage' => $step['usage'] ?? null,
                'optional' => (bool) ($step['optional'] ?? false),
                'technique' => $step['technique'] ?? null,
                'coverage' => $step['coverage'] ?? null,
            ]);

            $model->paints()->attach($this->mixToPivot($step['mix'] ?? [], "ricetta {$recipe->slug}"));
        }
    }

    private function importProjects(): void
    {
        foreach ($this->read('projects.json') as $index => $project) {
            $model = Project::updateOrCreate(['slug' => $project['slug']], [
                'name' => $project['name'],
                'subtitle' => $project['subtitle'] ?? null,
                'theme' => $project['theme'] ?? null,
                'status' => $project['status'],
                'intro' => $project['intro'] ?? null,
                'armor_label' => $project['armor_label'] ?? null,
                'default_bases' => $this->checkRecipeSlugs($project['default_bases'] ?? [], "progetto {$project['slug']}"),
                'extra_recipes' => $this->checkRecipeSlugs(self::EXTRA_PROJECT_RECIPES[$project['slug']] ?? [], "progetto {$project['slug']}"),
                'position' => $index + 1,
            ]);
            $this->count('progetti');

            foreach ($project['links'] ?? [] as $linkIndex => $link) {
                ProjectLink::updateOrCreate(['project_id' => $model->id, 'position' => $linkIndex + 1], [
                    'title' => $link['title'],
                    'url' => $link['url'],
                    'description' => $link['description'] ?? null,
                ]);
            }

            $groupIds = [];
            foreach ($project['groups'] ?? [] as $groupIndex => $group) {
                $groupIds[$group['slug']] = CharacterGroup::updateOrCreate(
                    ['project_id' => $model->id, 'slug' => $group['slug']],
                    ['name' => $group['name'], 'position' => $groupIndex + 1],
                )->id;
            }

            foreach ($project['characters'] ?? [] as $characterIndex => $character) {
                $this->importCharacter($model, $character, $characterIndex + 1, $groupIds);
            }

            // Guides first: armor types point to them.
            $guideIds = [];
            foreach ($project['guides'] ?? [] as $guide) {
                $guideIds[$guide['slug']] = $this->importGuide($model, $guide)->id;
            }

            foreach ($project['armor_types'] ?? [] as $armor) {
                $this->importArmorType($model, $armor, $guideIds);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $character
     * @param  array<string, int>  $groupIds
     */
    private function importCharacter(Project $project, array $character, int $position, array $groupIds): void
    {
        $label = "personaggio {$project->slug}/{$character['slug']}";

        $model = Character::updateOrCreate(['project_id' => $project->id, 'slug' => $character['slug']], [
            'user_id' => null,
            'character_group_id' => $groupIds[$character['group']]
                ?? throw new RuntimeException("Gruppo sconosciuto \"{$character['group']}\" nel {$label}."),
            'name' => $character['name'],
            'alias_it' => $character['alias_it'] ?? null,
            'subtitle' => $character['subtitle'] ?? null,
            'search_query' => $character['search_query'] ?? null,
            'versions_note' => $character['versions_note'] ?? null,
            'no_face' => (bool) ($character['no_face'] ?? false),
            'no_eyes' => (bool) ($character['no_eyes'] ?? false),
            'bases' => isset($character['bases']) ? $this->checkRecipeSlugs($character['bases'], $label) : null,
            'tips' => $character['tips'] ?? [],
            'source' => 'catalogo',
            'position' => $position,
        ]);
        $this->count('personaggi');

        foreach ($character['zones'] ?? [] as $zone) {
            $this->importZone($project, $model, null, $zone);
        }

        foreach ($character['versions'] ?? [] as $version) {
            $versionModel = CharacterVersion::updateOrCreate(['character_id' => $model->id, 'slug' => $version['slug']], [
                'position' => $version['position'],
                'label' => $version['label'],
                'subtitle' => $version['subtitle'] ?? null,
                'note' => $version['note'] ?? null,
            ]);
            $this->count('versioni');

            foreach ($version['zones'] ?? [] as $zone) {
                $this->importZone($project, $model, $versionModel, $zone);
            }
        }

        // Every catalog character has at least one version (it carries the
        // reference photo). Only columns that exist when this importer
        // first runs, inside its data migration, are written here.
        if (! $model->versions()->exists()) {
            CharacterVersion::create(['character_id' => $model->id, 'slug' => CharacterVersion::SINGLE_SLUG, 'position' => 1, 'label' => 'Unica']);
            $this->count('versioni');
        }
    }

    /**
     * @param  array<string, mixed>  $zone
     */
    private function importZone(Project $project, Character $character, ?CharacterVersion $version, array $zone): void
    {
        $recipeId = null;

        if (! empty($zone['inline_steps'])) {
            $recipeId = $this->importInlineRecipe($project, $character, $version, $zone)->id;
        } elseif (! empty($zone['recipe'])) {
            $recipeId = $this->recipeIds[$zone['recipe']]
                ?? throw new RuntimeException("Ricetta sconosciuta \"{$zone['recipe']}\" nella zona {$zone['name']} di {$character->slug}.");
        }

        Zone::updateOrCreate([
            'character_id' => $character->id,
            'character_version_id' => $version?->id,
            'position' => $zone['position'],
        ], [
            'name' => $zone['name'],
            'tab' => $zone['tab'] ?? null,
            'recipe_id' => $recipeId,
            'target_hex' => isset($zone['target_hex']) ? strtolower($zone['target_hex']) : null,
            'note' => $zone['note'] ?? null,
        ]);
        $this->count('zone');
    }

    /**
     * A zone's own step list becomes a hidden recipe, with a slug built
     * from where it lives so re-imports find the same row again.
     *
     * @param  array<string, mixed>  $zone
     */
    private function importInlineRecipe(Project $project, Character $character, ?CharacterVersion $version, array $zone): Recipe
    {
        $slug = implode('-', ['inline', $project->slug, $character->slug, $version?->slug ?? 'base', $zone['position']]);

        $recipe = Recipe::updateOrCreate(['slug' => $slug], [
            'recipe_category_id' => null,
            'title' => "{$character->name} · {$zone['name']}",
            'who' => null,
            'tip' => null,
            'is_inline' => true,
        ]);

        $this->replaceRecipeSteps($recipe, $zone['inline_steps']);
        $this->count('ricette delle zone');

        return $recipe;
    }

    /**
     * @param  array<string, mixed>  $guide
     */
    private function importGuide(Project $project, array $guide): Guide
    {
        $model = Guide::updateOrCreate(['project_id' => $project->id, 'slug' => $guide['slug']], [
            'position' => $guide['position'],
            'title' => $guide['title'],
            'intro' => $guide['intro'] ?? null,
        ]);

        $model->steps()->delete();

        foreach ($guide['steps'] ?? [] as $step) {
            $stepModel = $model->steps()->create([
                'position' => $step['position'],
                'title' => $step['title'],
                'description' => $step['description'] ?? null,
            ]);

            $stepModel->paints()->attach($this->mixToPivot($step['mix'] ?? [], "guida {$guide['slug']}"));
        }

        $this->count('guide');

        return $model;
    }

    /**
     * @param  array<string, mixed>  $armor
     * @param  array<string, int>  $guideIds
     */
    private function importArmorType(Project $project, array $armor, array $guideIds): void
    {
        $guideId = null;
        if (! empty($armor['guide'])) {
            $guideId = $guideIds[$armor['guide']]
                ?? throw new RuntimeException("Guida sconosciuta \"{$armor['guide']}\" nell'armatura {$armor['slug']}.");
        }

        $model = ArmorType::updateOrCreate(['project_id' => $project->id, 'slug' => $armor['slug']], [
            'position' => $armor['position'],
            'title' => $armor['title'],
            'who' => $armor['who'] ?? null,
            'description' => $armor['description'] ?? null,
            'guide_id' => $guideId,
        ]);

        $recipes = [];
        foreach ($armor['recipes'] ?? [] as $index => $slug) {
            $recipeId = $this->recipeIds[$slug]
                ?? throw new RuntimeException("Ricetta sconosciuta \"{$slug}\" nell'armatura {$armor['slug']}.");
            $recipes[$recipeId] = ['position' => $index + 1];
        }

        $model->recipes()->sync($recipes);
        $this->count('tipi di armatura');
    }

    private function importPath(): void
    {
        foreach ($this->read('beginner_path.json') as $step) {
            [$route, $anchor] = $this->parsePathLink($step['link'] ?? null);

            PathStep::updateOrCreate(['position' => $step['position']], [
                'title' => $step['title'],
                'description' => $step['description'] ?? null,
                'link_route' => $route,
                'link_anchor' => $anchor,
            ]);
            $this->count('passi del percorso');
        }
    }

    private function importTutorials(): void
    {
        foreach ($this->read('tutorials.json') as $tutorial) {
            Tutorial::updateOrCreate(['position' => $tutorial['position']], [
                'title' => $tutorial['title'],
                'query' => $tutorial['query'],
                'description' => $tutorial['description'] ?? null,
            ]);
            $this->count('tutorial');
        }
    }

    /**
     * "#/tecniche/t-prep" → ['resina.techniques.index', 't-prep'].
     *
     * @return array{0: ?string, 1: ?string}
     */
    private function parsePathLink(?string $link): array
    {
        if ($link === null || $link === '') {
            return [null, null];
        }

        $parts = array_values(array_filter(explode('/', Str::after($link, '#'))));
        $route = self::PATH_LINK_ROUTES[$parts[0] ?? '']
            ?? throw new RuntimeException("Link del percorso non riconosciuto: {$link}");

        return [$route, $parts[1] ?? null];
    }

    /**
     * @param  array<int, array{paint: string, drops: int}>  $mix
     * @return array<int, array{drops: int}>
     */
    private function mixToPivot(array $mix, string $context): array
    {
        $pivot = [];

        foreach ($mix as $item) {
            $paintId = $this->paintIds[$item['paint']]
                ?? throw new RuntimeException("Colore sconosciuto \"{$item['paint']}\" nella {$context}.");
            $pivot[$paintId] = ['drops' => $item['drops']];
        }

        return $pivot;
    }

    /**
     * @param  array<int, string>  $slugs
     * @return array<int, string>
     */
    private function checkRecipeSlugs(array $slugs, string $context): array
    {
        foreach ($slugs as $slug) {
            if (! isset($this->recipeIds[$slug])) {
                throw new RuntimeException("Ricetta sconosciuta \"{$slug}\" nel {$context}.");
            }
        }

        return array_values($slugs);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function read(string $file): array
    {
        $path = $this->dataPath.'/'.$file;

        if (! is_file($path)) {
            throw new RuntimeException("File dati mancante: {$path}");
        }

        return json_decode(file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);
    }

    private function count(string $kind): void
    {
        $this->counts[$kind] = ($this->counts[$kind] ?? 0) + 1;
    }
}
