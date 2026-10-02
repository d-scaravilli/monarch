<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Character;
use App\Models\Resina\GuideText;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeStep;
use App\Models\Resina\StepTitle;
use App\Models\Resina\TechniqueGuide;
use App\Models\User;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\TechniqueGuideImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaTechniqueGuideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
    }

    public function test_the_instructions_are_imported_by_their_migration(): void
    {
        $this->assertSame(['coprente', 'sottile', 'wash', 'drybrush', 'velatura', 'spigoli', 'punta'], TechniqueGuide::orderBy('position')->pluck('code')->all());
        $this->assertSame(22, StepTitle::count());
        $this->assertSame(array_keys(GuideText::LABELS), GuideText::orderBy('id')->pluck('key')->all());

        $wash = TechniqueGuide::where('code', 'wash')->firstOrFail();
        $this->assertSame(45, $wash->wait_minutes);
        $this->assertCount(5, $wash->steps);
        $this->assertStringContainsString('{pennello}', GuideText::where('key', 'light_metallico_drybrush')->value('lines')[0]);
    }

    public function test_importing_again_creates_no_duplicates_and_restores_the_texts(): void
    {
        TechniqueGuide::where('code', 'wash')->update(['name' => 'Cambiato']);

        app(TechniqueGuideImporter::class)->import();
        $this->artisan('resina:importa')->assertSuccessful();

        $this->assertSame(7, TechniqueGuide::count());
        $this->assertSame(22, StepTitle::count());
        $this->assertSame(6, GuideText::count());
        $this->assertSame('Wash (lavatura)', TechniqueGuide::where('code', 'wash')->value('name'));
    }

    public function test_the_payload_for_the_painting_mode_has_guides_titles_and_texts(): void
    {
        $payload = app(ClientPayload::class)->techniqueGuides();

        $this->assertSame('Strato coprente', $payload['guides']['coprente']['name']);
        $this->assertSame(['pattern' => '^prima mano', 'title' => 'Prima mano di copertura'], $payload['titles'][0]);
        $this->assertCount(3, $payload['texts']['metallico_preparazione']);
    }

    public function test_the_admin_edits_texts_one_line_per_item_but_not_the_criteria(): void
    {
        $admin = $this->admin();
        $title = StepTitle::where('pattern', '^ombra')->firstOrFail();

        $this->actingAs($admin)->get(route('resina.technique-guides.edit'))->assertOk()->assertSee('Strato coprente')->assertSee('^ombra');

        $this->actingAs($admin)->put(route('resina.technique-guides.update'), [
            'guides' => ['wash' => [
                'name' => 'Wash',
                'preparation' => "Agita.\n\n  Metti 3 gocce su un piattino.  \n",
                'steps' => "Uno.\nDue.",
                'wait_minutes' => 30,
                'result' => 'Incavi scuri.',
                'mistakes' => 'Pozze.',
            ]],
            'titles' => [$title->id => 'Ombra nelle parti basse'],
            'texts' => ['shade_tmm' => 'Nota nuova.'],
        ])->assertRedirect(route('resina.technique-guides.edit'));

        $wash = TechniqueGuide::where('code', 'wash')->firstOrFail();
        $this->assertSame(['Agita.', 'Metti 3 gocce su un piattino.'], $wash->preparation);
        $this->assertSame([30, ['Uno.', 'Due.']], [$wash->wait_minutes, $wash->steps]);
        $this->assertSame(['Ombra nelle parti basse', '^ombra'], [$title->refresh()->title, $title->pattern]);
        $this->assertSame(['Nota nuova.'], GuideText::where('key', 'shade_tmm')->value('lines'));
        // Untouched technique stays as it was.
        $this->assertSame('Strato coprente', TechniqueGuide::where('code', 'coprente')->value('name'));
    }

    public function test_only_the_admin_edits_the_instructions(): void
    {
        $painter = User::factory()->create();
        Module::where('slug', 'resina')->firstOrFail()->users()->attach($painter);

        $this->actingAs($painter)->get(route('resina.technique-guides.edit'))->assertForbidden();
        $this->actingAs($painter)->put(route('resina.technique-guides.update'), ['guides' => []])->assertForbidden();
        $this->actingAs($painter)->get(route('resina.techniques.index'))->assertOk()->assertDontSee(route('resina.technique-guides.edit'));
    }

    public function test_every_step_has_a_technique_with_instructions(): void
    {
        $codes = TechniqueGuide::pluck('code');

        $this->assertSame(0, RecipeStep::whereNull('technique')->count());
        $this->assertSame(0, RecipeStep::whereNotIn('technique', $codes)->count());
    }

    public function test_the_approved_corrections_are_in_the_catalog(): void
    {
        $eyes = Recipe::where('slug', 'eyes')->firstOrFail()->steps;
        $this->assertSame(['iride', 'iride', 'iride', 'iride', 'iride'], $eyes->whereNotNull('choice_group')->pluck('choice_group')->values()->all());
        $this->assertSame([3, 4, 5, 6, 7], $eyes->whereNotNull('choice_group')->pluck('position')->values()->all());

        $this->assertTrue(Recipe::where('slug', 'face')->firstOrFail()->steps->firstWhere('role', 'Cicatrice')->optional);

        foreach (['cm-pearl', 'cm-icy', 'cm-orange'] as $slug) {
            $this->assertSame('wash', Recipe::where('slug', $slug)->firstOrFail()->steps->firstWhere('role', 'Ombra')->technique, $slug);
        }
        $this->assertSame('coprente', Recipe::where('slug', 'metal-oldgold')->firstOrFail()->steps->firstWhere('role', 'Mix')->technique);
        $this->assertSame('Su tutta la zona: bianco perlato.', Recipe::where('slug', 'cm-pearl')->firstOrFail()->steps->firstWhere('role', 'Mix')->usage);
    }

    public function test_the_correction_migration_and_the_data_files_agree(): void
    {
        $migration = require database_path('migrations/2026_10_03_100001_add_choice_group_and_correct_resina_steps.php');
        $changes = (new ReflectionClass($migration))->getConstant('CHANGES');

        // The same steps, read from the JSON the importer (and "Reimporta catalogo") uses.
        $jsonSteps = [];
        foreach (json_decode(file_get_contents(database_path('data/resina/recipes.json')), true) as $recipe) {
            foreach ($recipe['steps'] as $step) {
                $jsonSteps[$recipe['slug'].'#'.$step['position']] = $step;
            }
        }
        foreach (json_decode(file_get_contents(database_path('data/resina/projects.json')), true) as $project) {
            foreach ($project['characters'] as $character) {
                $scopes = [['base', $character['zones']], ...array_map(fn ($v) => [$v['slug'], $v['zones'] ?? []], $character['versions'] ?? [])];
                foreach ($scopes as [$scope, $zones]) {
                    foreach ($zones as $zone) {
                        foreach ($zone['inline_steps'] ?? [] as $step) {
                            $jsonSteps[implode('-', ['inline', $project['slug'], $character['slug'], $scope, $zone['position']]).'#'.$step['position']] = $step;
                        }
                    }
                }
            }
        }

        $this->assertCount(130, $changes);
        foreach ($changes as [$slug, $position, $values]) {
            $step = $jsonSteps[$slug.'#'.$position] ?? null;
            $this->assertNotNull($step, "{$slug} #{$position}");
            foreach ($values as $field => $value) {
                $this->assertSame($value, $step[$field] ?? null, "{$slug} #{$position} {$field}");
            }
            $dbStep = Recipe::where('slug', $slug)->firstOrFail()->steps()->where('position', $position)->firstOrFail();
            foreach ($values as $field => $value) {
                $this->assertEquals($value, $dbStep->{$field}, "DB {$slug} #{$position} {$field}");
            }
        }
    }

    public function test_the_recipe_editor_keeps_the_choice_group(): void
    {
        $eyes = Recipe::where('slug', 'eyes')->with('steps.paints')->firstOrFail();
        $steps = $eyes->steps->map(fn (RecipeStep $step) => [
            'role' => $step->role, 'usage' => $step->usage, 'optional' => $step->optional ? '1' : '0',
            'technique' => $step->technique, 'coverage' => $step->coverage, 'choice_group' => $step->choice_group,
            'paints' => $step->paints->map(fn ($paint) => ['paint_id' => $paint->id, 'drops' => $paint->pivot->drops])->all(),
        ])->all();

        $this->actingAs($this->admin())->put(route('resina.recipes.update', $eyes), [
            'title' => $eyes->title, 'recipe_category_id' => $eyes->recipe_category_id, 'steps' => $steps,
        ])->assertRedirect();

        $this->assertSame(5, $eyes->steps()->where('choice_group', 'iride')->count());
    }

    public function test_copying_a_character_puts_the_versions_zones_first(): void
    {
        $admin = $this->admin();
        Module::where('slug', 'resina')->firstOrFail();

        $this->actingAs($admin)->post(route('resina.figures.copy', Character::where('slug', 'seiya')->value('id')))->assertRedirect();

        // Anime V1 (the first version): Cloth, Parti rosse, Tuta come before the shared Gemme.
        $names = $admin->resinFigures()->firstOrFail()->zones()->orderBy('position')->pluck('name')->all();
        $this->assertLessThan(array_search('Gemme', $names, true), array_search('Cloth', $names, true));
        $this->assertLessThan(array_search('Gemme', $names, true), array_search('Parti rosse della cloth', $names, true));
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }
}
