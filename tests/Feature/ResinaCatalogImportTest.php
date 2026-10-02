<?php

namespace Tests\Feature;

use App\Models\Resina\ArmorType;
use App\Models\Resina\Brush;
use App\Models\Resina\Character;
use App\Models\Resina\Paint;
use App\Models\Resina\PathStep;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\UserPaint;
use App\Models\Resina\Zone;
use App\Models\User;
use App\Services\Resina\CatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The catalog arrives through a data migration (the server only runs
 * `migrate --force`), so RefreshDatabase has already imported it here.
 */
class ResinaCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private const CATALOG_TABLES = [
        'resin_paints', 'resin_shop_suggestions', 'resin_recipe_categories', 'resin_recipes',
        'resin_recipe_steps', 'resin_recipe_step_paints', 'resin_projects', 'resin_project_links',
        'resin_character_groups', 'resin_characters', 'resin_character_versions', 'resin_zones',
        'resin_guides', 'resin_guide_steps', 'resin_guide_step_paints', 'resin_armor_types',
        'resin_armor_type_recipe', 'resin_path_steps', 'resin_tutorials',
    ];

    public function test_migrations_import_the_whole_catalog(): void
    {
        $this->assertSame(28, Paint::count());
        $this->assertSame(12, DB::table('resin_shop_suggestions')->count());
        $this->assertSame(80, Recipe::catalog()->count());
        $this->assertSame(18, Recipe::where('is_inline', true)->count());
        $this->assertSame(3, Project::count());
        $this->assertSame(35, Character::catalog()->count());
        // 36 imported versions + "Unica" for the 23 characters that had none.
        $this->assertSame(59, DB::table('resin_character_versions')->count());
        $this->assertSame(203, Zone::count());
        $this->assertSame(5, DB::table('resin_guides')->count());
        $this->assertSame(9, ArmorType::count());
        $this->assertSame(11, PathStep::count());
        $this->assertSame(12, DB::table('resin_tutorials')->count());
    }

    public function test_running_the_import_again_creates_no_duplicates(): void
    {
        $before = $this->catalogCounts();

        app(CatalogImporter::class)->import();
        $this->artisan('resina:importa')->assertSuccessful();

        $this->assertSame($before, $this->catalogCounts());
    }

    public function test_recipe_steps_keep_the_json_painting_order_and_drops(): void
    {
        $json = collect($this->readDataFile('recipes.json'))->firstWhere('slug', 'skin-tan');
        $recipe = Recipe::where('slug', 'skin-tan')->with('steps.paints')->firstOrFail();

        $this->assertSame('pelle', $recipe->category->slug);
        $this->assertSame(array_column($json['steps'], 'role'), $recipe->steps->pluck('role')->all());

        foreach ($json['steps'] as $index => $jsonStep) {
            $step = $recipe->steps[$index];
            $this->assertSame($jsonStep['optional'], $step->optional);
            $this->assertSame($jsonStep['technique'], $step->technique);
            $this->assertSame($jsonStep['coverage'], $step->coverage);
            $this->assertEquals(
                collect($jsonStep['mix'])->pluck('drops', 'paint')->sortKeys()->all(),
                $step->paints->mapWithKeys(fn (Paint $paint) => [$paint->code => $paint->pivot->drops])->sortKeys()->all(),
            );
        }
    }

    public function test_inline_zone_steps_become_a_hidden_recipe_linked_to_the_zone(): void
    {
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $eyes = $seiya->baseZones()->where('name', 'Occhi')->with('recipe.steps')->firstOrFail();

        $this->assertTrue($eyes->recipe->is_inline);
        $this->assertSame('inline-saint-seiya-seiya-base-3', $eyes->recipe->slug);
        $this->assertSame(['Contorno', 'Bianco', 'Iride castana'], $eyes->recipe->steps->take(3)->pluck('role')->all());
        $this->assertNotContains($eyes->recipe->id, Recipe::catalog()->pluck('id'));
    }

    public function test_versions_keep_their_own_zones(): void
    {
        $seiya = Character::where('slug', 'seiya')->with('versions.zones.recipe')->firstOrFail();
        $firstVersion = $seiya->versions->firstWhere('slug', 'a1');

        $this->assertSame('Anime V1', $firstVersion->label);
        $this->assertSame('Cloth', $firstVersion->zones->first()->name);
        $this->assertSame('cm-pearl', $firstVersion->zones->first()->recipe->slug);
        $this->assertSame(4, $seiya->baseZones()->count());
    }

    public function test_projects_carry_bases_extra_recipes_and_armor_links(): void
    {
        $project = Project::where('slug', 'saint-seiya')->firstOrFail();

        $this->assertSame(['base-rock', 'base-marble'], $project->default_bases);
        $this->assertSame(['fx-cosmo', 'fx-fire', 'base-rock', 'base-marble', 'base-lava'], $project->extra_recipes);
        $this->assertSame('Cloth', $project->armor_label);

        $gold = ArmorType::where('slug', 'gold')->with('recipes', 'guide')->firstOrFail();
        $this->assertSame('metal-gold', $gold->recipes->first()->slug);
        $this->assertSame('gold', $gold->guide->slug);
    }

    public function test_path_links_are_mapped_to_module_routes(): void
    {
        $first = PathStep::where('position', 1)->firstOrFail();
        $brushes = PathStep::where('position', 4)->firstOrFail();

        $this->assertSame(['resina.techniques.index', 't-errori'], [$first->link_route, $first->link_anchor]);
        $this->assertSame(['resina.brushes.index', null], [$brushes->link_route, $brushes->link_anchor]);
    }

    public function test_reimport_restores_catalog_edits_and_leaves_personal_data_alone(): void
    {
        Recipe::where('slug', 'skin-tan')->update(['title' => 'Modificata a mano']);

        $user = User::factory()->create();
        $figure = Character::factory()->personal($user)->create(['name' => 'La mia figura']);
        $customPaint = UserPaint::factory()->create(['user_id' => $user->id]);
        $brush = Brush::factory()->create(['user_id' => $user->id]);

        app(CatalogImporter::class)->import();

        $this->assertSame('Pelle abbronzata (set Tanned Skin)', Recipe::where('slug', 'skin-tan')->value('title'));
        $this->assertModelExists($figure);
        $this->assertModelExists($customPaint);
        $this->assertModelExists($brush);
    }

    /**
     * @return array<string, int>
     */
    private function catalogCounts(): array
    {
        return collect(self::CATALOG_TABLES)->mapWithKeys(fn (string $table) => [$table => DB::table($table)->count()])->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function readDataFile(string $file): array
    {
        return json_decode(file_get_contents(database_path('data/resina/'.$file)), true);
    }
}
