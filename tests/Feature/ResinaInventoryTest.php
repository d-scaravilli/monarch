<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Brush;
use App\Models\Resina\ShopSuggestion;
use App\Models\Resina\UserPaint;
use App\Models\User;
use App\Services\Resina\StarterKit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ResinaInventoryTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_my_paints_page_lists_the_inventory_with_codes_and_links_to_the_shop(): void
    {
        $this->actingAs($this->painter())->get(route('resina.paints.index'))
            ->assertOk()
            ->assertSee('Bianco Teschio')
            ->assertSee('72.001')
            ->assertSee(route('resina.shop.index'));
    }

    public function test_a_custom_paint_can_be_added(): void
    {
        $painter = $this->painter();

        $this->actingAs($painter)->post(route('resina.paints.store'), [
            'name' => 'Royal Purple', 'code' => '72.016', 'hex' => '#5B3A86', 'type' => 'normal',
        ])->assertRedirect(route('resina.paints.index'));

        $custom = $painter->resinPaints()->whereNull('paint_id')->firstOrFail();
        $this->assertSame(['Royal Purple', '72.016', '#5b3a86'], [$custom->name, $custom->code, $custom->hex]);
    }

    public function test_a_custom_paint_needs_a_name_and_a_real_color(): void
    {
        $this->actingAs($this->painter())
            ->post(route('resina.paints.store'), ['name' => '', 'hex' => 'viola', 'type' => 'normal'])
            ->assertSessionHasErrors(['name', 'hex']);
    }

    public function test_only_my_custom_paints_can_be_removed(): void
    {
        $painter = $this->painter();
        $custom = UserPaint::factory()->create(['user_id' => $painter->id]);
        $starter = $painter->resinPaints()->whereNotNull('paint_id')->firstOrFail();
        $someoneElses = UserPaint::factory()->create();

        $table = Livewire::actingAs($painter)->test('resina-paints-table')->assertSee($custom->name);
        $table->call('remove', $starter->id);
        $table->call('remove', $someoneElses->id);
        $table->call('remove', $custom->id);

        $this->assertModelMissing($custom);
        $this->assertModelExists($starter);
        $this->assertModelExists($someoneElses);
    }

    public function test_the_paints_table_filters_by_search_and_type(): void
    {
        $painter = $this->painter();

        Livewire::actingAs($painter)->test('resina-paints-table')
            ->set('search', 'Teschio')
            ->assertSee('Bianco Teschio')
            ->assertDontSee('Bianco Osso')
            ->set('search', '')
            ->set('type', 'metallic')
            ->assertSee('Imperial Gold Base')
            ->assertDontSee('Bianco Teschio');
    }

    public function test_bought_suggestions_join_the_inventory_once(): void
    {
        $painter = $this->painter();
        $suggestion = ShopSuggestion::where('code', '72.016')->firstOrFail();

        $this->actingAs($painter)->post(route('resina.paints.buy', $suggestion))->assertRedirect();
        $this->actingAs($painter)->post(route('resina.paints.buy', $suggestion))->assertRedirect();

        $this->assertSame(1, $painter->resinPaints()->where('code', '72.016')->count());
        $this->actingAs($painter)->get(route('resina.paints.index'))->assertOk();
    }

    public function test_brushes_can_be_edited_added_and_removed(): void
    {
        $painter = $this->painter();
        $brush = $painter->resinBrushes()->where('type', 'tondo')->firstOrFail();

        $this->actingAs($painter)->patchJson(route('resina.brushes.update', $brush), ['type' => 'liner', 'size' => ' 5/0 '])
            ->assertOk()
            ->assertJsonFragment(['id' => $brush->id, 'type' => 'liner', 'size' => '5/0']);

        $this->actingAs($painter)->postJson(route('resina.brushes.store'))->assertOk()->assertJsonCount(17, 'brushes');

        $this->actingAs($painter)->deleteJson(route('resina.brushes.destroy', $brush))->assertOk()->assertJsonCount(16, 'brushes');
        $this->assertModelMissing($brush);
    }

    public function test_only_one_brush_is_kept_for_metallics(): void
    {
        $painter = $this->painter();
        [$first, $second] = $painter->resinBrushes()->take(2)->get();

        $this->actingAs($painter)->patchJson(route('resina.brushes.update', $first), ['metallic_only' => true])->assertOk();
        $this->actingAs($painter)->patchJson(route('resina.brushes.update', $second), ['metallic_only' => true])->assertOk();

        $this->assertSame([$second->id], $painter->resinBrushes()->where('metallic_only', true)->pluck('id')->all());
    }

    public function test_the_brush_kit_can_be_restored_from_the_page(): void
    {
        $painter = $this->painter();
        $painter->resinBrushes()->limit(10)->delete();

        $this->actingAs($painter)->postJson(route('resina.brushes.reset'))->assertOk()->assertJsonCount(16, 'brushes');
    }

    public function test_someone_elses_brush_is_out_of_reach(): void
    {
        $painter = $this->painter();
        $other = Brush::factory()->create();

        $this->actingAs($painter)->patchJson(route('resina.brushes.update', $other), ['size' => '1'])->assertNotFound();
        $this->actingAs($painter)->deleteJson(route('resina.brushes.destroy', $other))->assertNotFound();
        $this->assertModelExists($other);
    }

    public function test_invalid_brush_types_are_rejected(): void
    {
        $painter = $this->painter();
        $brush = $painter->resinBrushes()->firstOrFail();

        $this->actingAs($painter)->patchJson(route('resina.brushes.update', $brush), ['type' => 'rullo'])->assertUnprocessable();
    }

    public function test_the_brushes_page_renders(): void
    {
        $this->actingAs($this->painter())->get(route('resina.brushes.index'))
            ->assertOk()
            ->assertSee('Ripristina il kit Nicpro')
            ->assertSee('A cosa serve ognuno');
    }

    private function painter(): User
    {
        $painter = User::factory()->create();
        $this->module->users()->attach($painter);
        app(StarterKit::class)->ensureFor($painter);

        return $painter;
    }
}
