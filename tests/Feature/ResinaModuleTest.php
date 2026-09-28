<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Brush;
use App\Models\Resina\Character;
use App\Models\Resina\Paint;
use App\Models\Resina\Recipe;
use App\Models\Resina\SavedMix;
use App\Models\Resina\StepProgress;
use App\Models\Resina\Zone;
use App\Models\User;
use App\Services\Resina\StarterKit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaModuleTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        Role::create(['name' => 'member']);

        // Registered by a data migration, like the catalog.
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_the_module_is_registered_by_its_migration(): void
    {
        $this->assertSame('3D - Resina', $this->module->name);
        $this->assertSame('paint-brush', $this->module->icon);
        $this->assertSame('purple', $this->module->color);
        $this->assertTrue($this->module->is_active);
    }

    public function test_entering_the_module_lands_on_its_home(): void
    {
        $this->actingAs($this->admin())
            ->get(route('modules.enter', $this->module))
            ->assertRedirect(route('resina.home'));
    }

    public function test_only_users_with_access_can_open_the_module(): void
    {
        $outsider = User::factory()->create();
        $this->actingAs($outsider)->get(route('resina.home'))->assertForbidden();

        $painter = User::factory()->create();
        $this->module->users()->attach($painter);

        $this->actingAs($painter)->get(route('resina.home'))
            ->assertOk()
            ->assertSee('La tua mensola')
            ->assertSee('Saint Seiya')
            ->assertSee('Bianco Teschio');
    }

    public function test_the_starter_kit_is_handed_out_once_on_the_first_visit(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->get(route('resina.home'))->assertOk()->assertSee('28 flaconi e 16 pennelli');

        $this->assertSame(Paint::count(), $painter->resinPaints()->whereNotNull('paint_id')->count());
        $this->assertSame(16, $painter->resinBrushes()->count());
        $this->assertSame(1, $painter->resinBrushes()->where('type', 'spot')->count());

        // Brushes deleted on purpose must not come back on the next visit.
        $painter->resinBrushes()->delete();
        $this->actingAs($painter)->get(route('resina.home'))->assertOk();

        $this->assertSame(0, $painter->resinBrushes()->count());
        $this->assertSame(28, $painter->resinPaints()->count());
    }

    public function test_the_brush_kit_can_be_restored(): void
    {
        $painter = $this->painter();
        app(StarterKit::class)->ensureFor($painter);
        $painter->resinBrushes()->limit(5)->delete();

        app(StarterKit::class)->resetBrushes($painter);

        $this->assertSame(16, $painter->resinBrushes()->count());
    }

    public function test_the_sidebar_shows_manage_only_to_the_admin(): void
    {
        $this->actingAs($this->admin())->get(route('resina.home'))
            ->assertSee(route('modules.settings.edit', $this->module));

        $this->actingAs($this->painter())->get(route('resina.home'))
            ->assertOk()
            ->assertDontSee(route('modules.settings.edit', $this->module));
    }

    public function test_the_manage_page_offers_reset_and_reimport(): void
    {
        $this->actingAs($this->admin())->get(route('modules.settings.edit', $this->module))
            ->assertOk()
            ->assertSee('Zona pericolosa')
            ->assertSee('Reimporta catalogo iniziale')
            ->assertSee('Sovrascrive tutte le modifiche fatte al catalogo')
            ->assertDontSee('Elimina tutte le notifiche');
    }

    public function test_resetting_wipes_everyones_personal_data_and_photos_but_keeps_the_catalog(): void
    {
        Storage::fake('public');
        $painter = $this->painter();
        app(StarterKit::class)->ensureFor($painter);

        $photo = UploadedFile::fake()->image('figura.jpg')->store('resina/figures', 'public');
        $figure = Character::factory()->personal($painter)->create(['reference_image_path' => $photo, 'source' => 'foto']);
        $figureRecipe = Recipe::factory()->inline()->create();
        Zone::factory()->create(['character_id' => $figure->id, 'recipe_id' => $figureRecipe->id]);
        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        StepProgress::create(['user_id' => $painter->id, 'character_id' => $seiya->id, 'zone_key' => 'auto:eyes', 'step_position' => 1, 'done_at' => now()]);
        SavedMix::create(['user_id' => $painter->id, 'name' => 'Pelle', 'mix' => ['p1' => 2]]);
        $catalogBefore = [Recipe::count() - 1, Character::catalog()->count(), Zone::count() - 1];

        $this->actingAs($this->admin())
            ->delete(route('modules.settings.reset', $this->module), ['confirm_name' => '3D - Resina'])
            ->assertRedirect(route('modules.settings.edit', $this->module));

        $this->assertModelMissing($figure);
        $this->assertModelMissing($figureRecipe);
        Storage::disk('public')->assertMissing($photo);
        $this->assertSame(0, $painter->resinPaints()->count());
        $this->assertSame(0, Brush::count());
        $this->assertSame(0, StepProgress::count());
        $this->assertSame(0, SavedMix::count());
        $this->assertNull($painter->resinProfile()->first());
        $this->assertSame($catalogBefore, [Recipe::count(), Character::catalog()->count(), Zone::count()]);
        $this->assertModelExists($painter);

        // Next visit: a fresh starter kit.
        $this->actingAs($painter)->get(route('resina.home'))->assertOk();
        $this->assertSame(16, $painter->resinBrushes()->count());
    }

    public function test_reset_needs_the_admin_and_the_module_name(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)
            ->delete(route('modules.settings.reset', $this->module), ['confirm_name' => '3D - Resina'])
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('modules.settings.reset', $this->module), ['confirm_name' => 'Resina'])
            ->assertStatus(422);
    }

    public function test_reimport_restores_the_imported_catalog(): void
    {
        Recipe::where('slug', 'skin-tan')->update(['title' => 'Cambiata']);

        $this->actingAs($this->admin())
            ->post(route('modules.settings.reimport', $this->module), ['confirm_name' => '3D - Resina'])
            ->assertRedirect(route('modules.settings.edit', $this->module));

        $this->assertSame('Pelle abbronzata (set Tanned Skin)', Recipe::where('slug', 'skin-tan')->value('title'));
    }

    public function test_reimport_is_only_for_resina_and_only_for_the_admin(): void
    {
        $palestra = Module::create(['slug' => 'palestra', 'name' => 'Palestra', 'icon' => 'fire', 'color' => 'orange', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->post(route('modules.settings.reimport', $palestra), ['confirm_name' => 'Palestra'])
            ->assertNotFound();

        $this->actingAs($this->painter())
            ->post(route('modules.settings.reimport', $this->module), ['confirm_name' => '3D - Resina'])
            ->assertForbidden();
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
        $painter->assignRole('member');
        $this->module->users()->attach($painter);

        return $painter;
    }
}
