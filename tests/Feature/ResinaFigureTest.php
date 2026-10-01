<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Character;
use App\Models\Resina\Recipe;
use App\Models\Resina\StepProgress;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ResinaFigureTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_a_new_figure_starts_with_a_skin_zone_and_opens_its_editor(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->post(route('resina.figures.store'), ['name' => 'Goku'])->assertRedirect();

        $figure = $painter->resinFigures()->with('zones.recipe')->firstOrFail();
        $this->assertSame(['Pelle'], $figure->zones->pluck('name')->all());
        $this->assertSame('skin-tan', $figure->zones->first()->recipe->slug);

        $this->actingAs($painter)->get(route('resina.figures.show', $figure))->assertOk()->assertSee('Modifica nome e zone')->assertSee('Goku');
        $this->actingAs($painter)->get(route('resina.figures.index'))->assertOk()->assertSee('Goku');
    }

    public function test_figures_are_private(): void
    {
        $figure = Character::factory()->personal()->create();
        $painter = $this->painter();

        $this->actingAs($painter)->get(route('resina.figures.show', $figure))->assertNotFound();
        $this->actingAs($painter)->put(route('resina.figures.update', $figure), ['name' => 'X'])->assertNotFound();
        $this->actingAs($painter)->delete(route('resina.figures.destroy', $figure))->assertNotFound();
        $this->actingAs($painter)->get(route('resina.figures.image', [$figure, 'originale']))->assertNotFound();
        $this->assertModelExists($figure);
    }

    public function test_a_catalog_character_is_not_a_figure(): void
    {
        $seiya = Character::where('slug', 'seiya')->firstOrFail();

        $this->actingAs($this->painter())->get(route('resina.figures.show', $seiya))->assertNotFound();
    }

    public function test_the_owner_renames_and_edits_zones(): void
    {
        $painter = $this->painter();
        $this->actingAs($painter)->post(route('resina.figures.store'), ['name' => 'Goku']);
        $figure = $painter->resinFigures()->firstOrFail();
        $skin = $figure->zones()->firstOrFail();

        $this->actingAs($painter)->put(route('resina.figures.update', $figure), [
            'name' => 'Goku SSJ',
            'zones' => [
                ['name' => 'Capelli', 'target_hex' => '#F2C928', 'tab' => 'capelli'],
                ['id' => $skin->id, 'name' => 'Pelle', 'recipe_id' => $skin->recipe_id],
            ],
        ])->assertRedirect(route('resina.figures.show', $figure));

        $this->assertSame('Goku SSJ', $figure->refresh()->name);
        $this->assertSame(['Capelli', 'Pelle'], $figure->zones()->pluck('name')->all());
    }

    public function test_copying_a_character_takes_the_chosen_version_without_automatic_face_and_bases(): void
    {
        $painter = $this->painter();
        $seiya = Character::where('slug', 'seiya')->with('versions')->firstOrFail();
        $manga = $seiya->versions->firstWhere('slug', 'm');
        $painter->resinCharacterVersions()->attach($manga->id, ['character_id' => $seiya->id]);

        $this->actingAs($painter)->post(route('resina.figures.copy', $seiya))->assertRedirect();

        $figure = $painter->resinFigures()->with('zones.recipe')->firstOrFail();
        $this->assertSame('Seiya (mia versione)', $figure->name);

        $names = $figure->zones->pluck('name')->all();
        $mangaNames = $seiya->zones()->where('character_version_id', $manga->id)->pluck('name')->all();
        foreach ($mangaNames as $name) {
            $this->assertContains($name, $names);
        }
        $this->assertNotContains('Labbra, guance e sopracciglia', $names);
        $this->assertSame($figure->zones->count(), count(array_unique($names)));
        $this->assertTrue($figure->zones->every(fn ($zone) => $zone->tab !== null));

        // Inline steps are copied, not shared.
        $eyes = $figure->zones->firstWhere('name', 'Occhi');
        $this->assertTrue($eyes->recipe->is_inline);
        $this->assertNotSame(Recipe::where('slug', 'inline-saint-seiya-seiya-base-3')->value('id'), $eyes->recipe_id);
        $this->assertSame(
            Recipe::where('slug', 'inline-saint-seiya-seiya-base-3')->firstOrFail()->steps()->pluck('role')->all(),
            $eyes->recipe->steps()->pluck('role')->all(),
        );
    }

    public function test_copying_a_character_without_an_eye_zone_adds_the_automatic_eyes(): void
    {
        $painter = $this->painter();
        $character = Character::catalog()
            ->where('no_face', false)->where('no_eyes', false)
            ->whereDoesntHave('zones', fn ($q) => $q->where('name', 'like', 'Occhi%'))
            ->firstOrFail();

        $this->actingAs($painter)->post(route('resina.figures.copy', $character))->assertRedirect();

        $eyes = $painter->resinFigures()->firstOrFail()->zones()->where('name', 'Occhi')->with('recipe')->firstOrFail();
        $this->assertSame(['eyes', 'volto'], [$eyes->recipe->slug, $eyes->tab]);
    }

    public function test_a_photo_guide_of_a_catalog_character_merges_the_photo_colors(): void
    {
        $painter = $this->painter();
        $seiya = Character::where('slug', 'seiya')->with('versions')->firstOrFail();
        $version = $seiya->versions->first();

        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$this->photos(),
            'character_id' => $seiya->id,
            'version_id' => $version->id,
            'colors' => [
                ['hex' => '#D9A07A', 'tab' => 'pelle', 'name' => ''],
                ['hex' => '#112233', 'tab' => 'vestiti', 'name' => 'Mantello'],
            ],
        ], ['Accept' => 'application/json'])->assertOk()->assertJsonStructure(['redirect']);

        $figure = $painter->resinFigures()->with('zones')->firstOrFail();
        $this->assertSame('Seiya (dalla foto)', $figure->name);
        $this->assertSame('foto', $figure->source);
        $this->assertSame($seiya->tips, $figure->tips);
        $this->assertStringStartsWith($version->label, $figure->note);

        $skin = $figure->zones->firstWhere('name', 'Pelle');
        $this->assertSame(['#d9a07a', null, 'Colore preso dalla tua foto.'], [$skin->target_hex, $skin->recipe_id, $skin->note]);
        $this->assertSame('vestiti', $figure->zones->firstWhere('name', 'Mantello')->tab);

        Storage::disk('local')->assertExists($figure->reference_image_path);
        Storage::disk('local')->assertExists($figure->reference_thumb_path);
        $this->assertStringStartsWith('resina/figures/'.$painter->id.'/', $figure->reference_image_path);
    }

    public function test_a_photo_guide_of_a_new_character_needs_at_least_one_color(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->post(route('resina.photo.store'), [...$this->photos(), 'name' => 'Goku'], ['Accept' => 'application/json'])
            ->assertUnprocessable();
        $this->assertSame(0, $painter->resinFigures()->count());
        $this->assertSame([], Storage::disk('local')->allFiles());

        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$this->photos(),
            'name' => 'Goku',
            'notes' => 'Versione Super Saiyan',
            'colors' => [['hex' => '#F2C928', 'tab' => 'capelli'], ['hex' => '#E76C25', 'tab' => 'vestiti']],
        ], ['Accept' => 'application/json'])->assertOk();

        $figure = $painter->resinFigures()->with('zones')->firstOrFail();
        $this->assertSame(['Goku', 'Versione Super Saiyan'], [$figure->name, $figure->note]);
        $this->assertSame(['Capelli', 'Tuta'], $figure->zones->pluck('name')->all());
    }

    public function test_only_jpeg_photos_of_reasonable_size_are_accepted(): void
    {
        $painter = $this->painter();
        $base = ['name' => 'Goku', 'colors' => [['hex' => '#F2C928', 'tab' => 'capelli']]];

        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$base,
            'photo' => UploadedFile::fake()->create('foto.heic', 800, 'image/heic'),
            'thumb' => UploadedFile::fake()->image('m.jpg', 200, 150),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('photo');

        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$base,
            'photo' => UploadedFile::fake()->image('foto.jpg', 4000, 3000),
            'thumb' => UploadedFile::fake()->image('m.jpg', 200, 150),
        ], ['Accept' => 'application/json'])->assertUnprocessable()->assertJsonValidationErrors('photo');
    }

    public function test_photos_are_served_only_to_their_owner(): void
    {
        $painter = $this->painter();
        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$this->photos(), 'name' => 'Goku', 'colors' => [['hex' => '#F2C928', 'tab' => 'capelli']],
        ], ['Accept' => 'application/json'])->assertOk();
        $figure = $painter->resinFigures()->firstOrFail();

        $this->actingAs($painter)->get(route('resina.figures.image', [$figure, 'miniatura']))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs($painter)->get(route('resina.figures.image', [$figure, 'originale']))->assertOk();
        $this->actingAs($this->painter())->get(route('resina.figures.image', [$figure, 'originale']))->assertNotFound();
    }

    public function test_deleting_a_figure_removes_its_photos_inline_steps_and_progress(): void
    {
        $painter = $this->painter();
        $this->actingAs($painter)->post(route('resina.figures.copy', Character::where('slug', 'seiya')->value('id')));
        $copied = $painter->resinFigures()->firstOrFail();
        $inlineId = $copied->zones()->whereHas('recipe', fn ($q) => $q->where('is_inline', true))->value('recipe_id');

        $this->actingAs($painter)->post(route('resina.photo.store'), [
            ...$this->photos(), 'name' => 'Goku', 'colors' => [['hex' => '#F2C928', 'tab' => 'capelli']],
        ], ['Accept' => 'application/json']);
        $photoFigure = $painter->resinFigures()->where('source', 'foto')->firstOrFail();
        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $photoFigure), ['zone_key' => 'auto:face', 'step_position' => 1, 'done' => true])->assertOk();

        $this->actingAs($painter)->delete(route('resina.figures.destroy', $photoFigure))->assertRedirect(route('resina.figures.index'));
        $this->actingAs($painter)->delete(route('resina.figures.destroy', $copied))->assertRedirect();

        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->assertSame(0, StepProgress::count());
        $this->assertNull(Recipe::find($inlineId));
        $this->assertSame(0, $painter->resinFigures()->count());
    }

    public function test_the_character_sheet_offers_the_copy_button_and_the_analysis_page_opens(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->get(route('resina.photo.create'))->assertOk()->assertSee('Analizza una foto')->assertDontSee('Analisi con Claude');
        $this->actingAs($painter)->get(route('resina.characters.show', ['saint-seiya', 'seiya']))->assertOk()->assertSee('Copia nelle mie figure');
    }

    /**
     * What the browser sends: the resized JPEG and its thumbnail.
     *
     * @return array{photo: UploadedFile, thumb: UploadedFile}
     */
    private function photos(): array
    {
        return [
            'photo' => UploadedFile::fake()->image('foto.jpg', 1600, 1200),
            'thumb' => UploadedFile::fake()->image('miniatura.jpg', 320, 240),
        ];
    }

    private function painter(): User
    {
        $painter = User::factory()->create();
        $this->module->users()->attach($painter);

        return $painter;
    }
}
