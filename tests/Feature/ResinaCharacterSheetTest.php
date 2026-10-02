<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Character;
use App\Models\Resina\Recipe;
use App\Models\Resina\StepProgress;
use App\Models\Resina\Zone;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaCharacterSheetTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_the_sheet_carries_the_character_its_versions_and_every_recipe_it_needs(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.characters.show', ['saint-seiya', 'seiya']))->assertOk();
        $payload = $this->payloadOf($response->getContent());

        $this->assertSame('Seiya', $payload['character']['name']);
        $this->assertCount(4, $payload['character']['base_zones']);
        $this->assertCount(6, $payload['character']['versions']);
        $this->assertSame($payload['character']['versions'][0]['id'], $payload['chosenVersionId']);
        $this->assertSame('panoramica', $payload['tab']);
        $this->assertSame([], $payload['done']);
        $this->assertSame(route('resina.figures.copy', Character::where('slug', 'seiya')->value('id')), $payload['copyUrl']);

        $slugs = array_column($payload['recipes'], 'slug');
        foreach (['eyes', 'face', 'base-rock', 'base-marble', 'skin-tan', 'inline-saint-seiya-seiya-base-3'] as $slug) {
            $this->assertContains($slug, $slugs);
        }
    }

    public function test_a_tab_in_the_url_opens_that_tab(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.characters.show', ['saint-seiya', 'seiya', 'armatura']))->assertOk();

        $this->assertSame('armatura', $this->payloadOf($response->getContent())['tab']);
    }

    public function test_a_character_only_opens_inside_its_own_project(): void
    {
        $this->actingAs($this->painter())->get(route('resina.characters.show', ['marvel', 'seiya']))->assertNotFound();
    }

    public function test_the_chosen_version_is_saved_per_user(): void
    {
        $painter = $this->painter();
        $other = $this->painter();
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $manga = $seiya->versions()->reorder('position', 'desc')->firstOrFail();

        $this->actingAs($painter)->postJson(route('resina.characters.version', $seiya), ['version_id' => $manga->id])->assertOk();
        $this->actingAs($painter)->postJson(route('resina.characters.version', $seiya), ['version_id' => $manga->id])->assertOk();

        $mine = $this->payloadOf($this->actingAs($painter)->get(route('resina.characters.show', ['saint-seiya', 'seiya']))->getContent());
        $theirs = $this->payloadOf($this->actingAs($other)->get(route('resina.characters.show', ['saint-seiya', 'seiya']))->getContent());

        $this->assertSame($manga->id, $mine['chosenVersionId']);
        $this->assertNotSame($manga->id, $theirs['chosenVersionId']);
        $this->assertSame(1, $painter->resinCharacterVersions()->count());
    }

    public function test_a_version_of_another_character_is_refused(): void
    {
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $shiryuVersion = Character::where('slug', 'shiryu')->firstOrFail()->versions()->firstOrFail();

        $this->actingAs($this->painter())->postJson(route('resina.characters.version', $seiya), ['version_id' => $shiryuVersion->id])
            ->assertUnprocessable();
    }

    public function test_done_steps_are_saved_and_cleared_one_by_one(): void
    {
        $painter = $this->painter();
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $skin = $seiya->baseZones()->where('name', 'Pelle')->firstOrFail();

        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'z'.$skin->id, 'step_position' => 1, 'done' => true])->assertOk();
        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'z'.$skin->id, 'step_position' => 1, 'done' => true])->assertOk();
        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'auto:eyes', 'step_position' => 2, 'done' => true])->assertOk();
        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'base:base-rock', 'step_position' => 1, 'done' => true])->assertOk();

        $payload = $this->payloadOf($this->actingAs($painter)->get(route('resina.characters.show', ['saint-seiya', 'seiya']))->getContent());
        $this->assertEqualsCanonicalizing(['z'.$skin->id.'|1', 'auto:eyes|2', 'base:base-rock|1'], $payload['done']);

        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'auto:eyes', 'step_position' => 2, 'done' => false])->assertOk();
        $this->assertSame(2, StepProgress::where('user_id', $painter->id)->count());
    }

    public function test_bad_progress_keys_are_refused(): void
    {
        $painter = $this->painter();
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $shiryuZone = Character::where('slug', 'shiryu')->firstOrFail()->zones()->firstOrFail();

        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'qualsiasi', 'step_position' => 1, 'done' => true])->assertUnprocessable();
        $this->actingAs($painter)->postJson(route('resina.progress.toggle', $seiya), ['zone_key' => 'z'.$shiryuZone->id, 'step_position' => 1, 'done' => true])->assertUnprocessable();
        $this->assertSame(0, StepProgress::count());
    }

    public function test_resetting_clears_only_my_progress_on_that_character(): void
    {
        $painter = $this->painter();
        $other = $this->painter();
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $shiryu = Character::where('slug', 'shiryu')->firstOrFail();

        foreach ([[$painter, $seiya], [$painter, $shiryu], [$other, $seiya]] as [$user, $character]) {
            $this->actingAs($user)->postJson(route('resina.progress.toggle', $character), ['zone_key' => 'auto:face', 'step_position' => 1, 'done' => true]);
        }

        $this->actingAs($painter)->deleteJson(route('resina.progress.reset', $seiya))->assertOk();

        $this->assertSame(0, StepProgress::where('user_id', $painter->id)->where('character_id', $seiya->id)->count());
        $this->assertSame(1, StepProgress::where('user_id', $painter->id)->where('character_id', $shiryu->id)->count());
        $this->assertSame(1, StepProgress::where('user_id', $other->id)->count());
    }

    public function test_someone_elses_personal_figure_is_out_of_reach(): void
    {
        $figure = Character::factory()->personal()->create();

        $this->actingAs($this->painter())->postJson(route('resina.progress.toggle', $figure), ['zone_key' => 'auto:face', 'step_position' => 1, 'done' => true])->assertNotFound();
        $this->actingAs($this->painter())->deleteJson(route('resina.progress.reset', $figure))->assertNotFound();
    }

    public function test_painters_cannot_edit_characters(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->get(route('resina.characters.edit', ['saint-seiya', 'seiya']))->assertForbidden();
        $this->actingAs($painter)->put(route('resina.characters.update', ['saint-seiya', 'seiya']), ['name' => 'X'])->assertForbidden();
        $this->actingAs($painter)->get(route('resina.versions.create', ['saint-seiya', 'seiya']))->assertForbidden();
    }

    public function test_the_admin_creates_a_character_with_recipe_and_free_color_zones(): void
    {
        $skin = Recipe::where('slug', 'skin-tan')->value('id');

        $this->actingAs($this->admin())->post(route('resina.characters.store', 'marvel'), [
            'name' => 'Loki',
            'bases' => [],
            'tips' => "Martello con drybrush.\n\nMantello rosso.",
            'zones' => [
                ['name' => 'Pelle', 'recipe_id' => $skin],
                ['name' => 'Mantello', 'recipe_id' => '', 'target_hex' => '#B22222', 'tab' => 'vestiti'],
            ],
        ])->assertRedirect(route('resina.characters.edit', ['marvel', 'loki']));

        $loki = Character::where('slug', 'loki')->with('zones')->firstOrFail();
        $this->assertSame(['Pelle', 'Mantello'], $loki->zones->pluck('name')->all());
        $this->assertSame(['#b22222', 'vestiti'], [$loki->zones[1]->target_hex, $loki->zones[1]->tab]);
        $this->assertSame(['Martello con drybrush.', 'Mantello rosso.'], $loki->tips);
        $this->assertNull($loki->bases);
    }

    public function test_a_zone_needs_a_recipe_or_a_color(): void
    {
        $this->actingAs($this->admin())->post(route('resina.characters.store', 'marvel'), [
            'name' => 'Loki',
            'zones' => [['name' => 'Mantello', 'recipe_id' => '']],
        ])->assertSessionHasErrors('zones.0.recipe_id');
    }

    public function test_editing_zones_reorders_keeps_inline_steps_and_removes_the_missing_ones(): void
    {
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $zones = $seiya->baseZones()->get();
        $eyes = $zones->firstWhere('name', 'Occhi');
        $rows = $zones->reverse()->reject(fn (Zone $zone) => $zone->name === 'Capelli')->map(fn (Zone $zone) => [
            'id' => $zone->id, 'name' => $zone->name, 'recipe_id' => $zone->recipe_id, 'target_hex' => $zone->target_hex, 'tab' => $zone->tab,
        ])->values()->all();

        $this->actingAs($this->admin())->put(route('resina.characters.update', ['saint-seiya', 'seiya']), [
            'name' => 'Seiya', 'character_group_id' => $seiya->character_group_id, 'zones' => $rows,
        ])->assertRedirect(route('resina.characters.show', ['saint-seiya', 'seiya']));

        $this->assertSame(array_column($rows, 'name'), $seiya->baseZones()->pluck('name')->all());
        $this->assertSame($eyes->recipe_id, $eyes->refresh()->recipe_id);
        $this->assertSame(0, $seiya->baseZones()->where('name', 'Capelli')->count());
    }

    public function test_a_new_zone_keeps_its_place_between_existing_ones(): void
    {
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        [$first, $second] = $seiya->baseZones()->take(2)->get();
        $row = fn (Zone $zone) => ['id' => $zone->id, 'name' => $zone->name, 'recipe_id' => $zone->recipe_id, 'target_hex' => $zone->target_hex];

        $this->actingAs($this->admin())->put(route('resina.characters.update', ['saint-seiya', 'seiya']), [
            'name' => 'Seiya',
            'zones' => [$row($first), ['name' => 'Cintura', 'target_hex' => '#553311'], $row($second)],
        ])->assertRedirect();

        $this->assertSame([$first->name, 'Cintura', $second->name], $seiya->baseZones()->pluck('name')->all());
    }

    public function test_a_zone_cannot_borrow_another_zones_inline_steps(): void
    {
        $foreignInline = Recipe::where('slug', 'inline-saint-seiya-shiryu-base-3')->value('id')
            ?? Recipe::where('is_inline', true)->where('slug', 'not like', 'inline-saint-seiya-seiya-%')->value('id');

        $this->actingAs($this->admin())->post(route('resina.characters.store', 'marvel'), [
            'name' => 'Loki',
            'zones' => [['name' => 'Occhi', 'recipe_id' => $foreignInline]],
        ])->assertSessionHasErrors('zones.0.recipe_id');
    }

    public function test_the_admin_adds_edits_and_removes_a_version(): void
    {
        $admin = $this->admin();
        $gold = Recipe::where('slug', 'metal-gold')->value('id');

        $this->actingAs($admin)->post(route('resina.versions.store', ['saint-seiya', 'seiya']), [
            'label' => 'Cloth divina', 'position' => 7, 'zones' => [['name' => 'Cloth', 'recipe_id' => $gold]],
        ])->assertSessionHasErrors('reference_token');

        Storage::fake('local');
        $token = $this->actingAs($admin)->post(route('resina.references.temporary'), [
            'photo' => UploadedFile::fake()->image('foto.jpg', 1200, 900),
            'thumb' => UploadedFile::fake()->image('m.jpg', 400, 300),
        ], ['Accept' => 'application/json'])->json('token');

        $this->actingAs($admin)->post(route('resina.versions.store', ['saint-seiya', 'seiya']), [
            'label' => 'Cloth divina', 'position' => 7, 'zones' => [['name' => 'Cloth', 'recipe_id' => $gold]], 'reference_token' => $token,
        ])->assertRedirect(route('resina.characters.edit', ['saint-seiya', 'seiya']));

        $version = Character::where('slug', 'seiya')->firstOrFail()->versions()->where('slug', 'cloth-divina')->firstOrFail();
        $this->assertSame(['Cloth'], $version->zones()->pluck('name')->all());

        $this->actingAs($admin)->put(route('resina.versions.update', ['saint-seiya', 'seiya', $version]), [
            'label' => 'Cloth divina', 'position' => 7, 'zones' => [['name' => 'Cloth', 'recipe_id' => $gold], ['name' => 'Ali', 'target_hex' => '#ffffff']],
        ])->assertRedirect();
        $this->assertSame(['Cloth', 'Ali'], $version->zones()->pluck('name')->all());

        $this->actingAs($admin)->delete(route('resina.versions.destroy', ['saint-seiya', 'seiya', $version]))->assertRedirect();
        $this->assertModelMissing($version);
    }

    public function test_deleting_a_character_takes_its_inline_recipes(): void
    {
        $inline = Recipe::where('slug', 'inline-saint-seiya-seiya-base-3')->firstOrFail();

        $this->actingAs($this->admin())->delete(route('resina.characters.destroy', ['saint-seiya', 'seiya']))
            ->assertRedirect(route('resina.projects.show', 'saint-seiya'));

        $this->assertSame(0, Character::where('slug', 'seiya')->count());
        $this->assertModelMissing($inline);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadOf(string $html): array
    {
        preg_match('#<script type="application/json" id="resina-character">(.*?)</script>#s', $html, $match);

        return json_decode($match[1], true);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        return $admin;
    }

    private function painter(): User
    {
        $painter = User::factory()->create();
        $this->module->users()->attach($painter);

        return $painter;
    }
}
