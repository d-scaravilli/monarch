<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Paint;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaRecipeTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_the_recipe_book_hands_every_catalog_recipe_to_the_page(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.recipes.index'))->assertOk();

        $payload = $this->payloadOf($response->getContent(), 'resina-recipes');

        $this->assertCount(80, $payload['recipes']);
        $this->assertNotContains(true, array_map(fn ($r) => str_starts_with($r['slug'], 'inline-'), $payload['recipes']));
        $this->assertCount(16, $payload['brushes']);
        $this->assertNull($payload['editUrl']);

        $skin = collect($payload['recipes'])->firstWhere('slug', 'skin-tan');
        $base = collect($skin['steps'])->firstWhere('role', 'Base');
        $this->assertSame(['p'.Paint::where('code', '72.004')->value('id') => 1], $base['mix']);
        $this->assertSame('coprente', $base['technique']);
        $this->assertSame(100, $base['coverage']);
    }

    public function test_only_the_admin_sees_the_edit_controls(): void
    {
        $response = $this->actingAs($this->admin())->get(route('resina.recipes.index'))->assertOk()->assertSee('Nuova ricetta');

        $this->assertStringContainsString('__SLUG__', $this->payloadOf($response->getContent(), 'resina-recipes')['editUrl']);

        $this->actingAs($this->painter())->get(route('resina.recipes.index'))->assertDontSee('Nuova ricetta');
    }

    public function test_painters_cannot_change_recipes(): void
    {
        $painter = $this->painter();
        $recipe = Recipe::where('slug', 'skin-tan')->firstOrFail();

        $this->actingAs($painter)->get(route('resina.recipes.create'))->assertForbidden();
        $this->actingAs($painter)->post(route('resina.recipes.store'), $this->validData())->assertForbidden();
        $this->actingAs($painter)->get(route('resina.recipes.edit', $recipe))->assertForbidden();
        $this->actingAs($painter)->put(route('resina.recipes.update', $recipe), $this->validData())->assertForbidden();
        $this->actingAs($painter)->delete(route('resina.recipes.destroy', $recipe))->assertForbidden();
    }

    public function test_the_admin_creates_a_recipe_with_steps_in_the_given_order(): void
    {
        $this->actingAs($this->admin())
            ->post(route('resina.recipes.store'), $this->validData())
            ->assertRedirect(route('resina.recipes.index').'#r-viola-profondo');

        $recipe = Recipe::where('slug', 'viola-profondo')->with('steps.paints')->firstOrFail();
        $this->assertSame(['Ombra', 'Base', 'Luce estrema'], $recipe->steps->pluck('role')->all());
        $this->assertSame([1, 2, 3], $recipe->steps->pluck('position')->all());
        $this->assertSame(3, $recipe->steps[1]->paints->sum('pivot.drops'));
        $this->assertTrue($recipe->steps[2]->optional);
    }

    public function test_updating_reorders_steps_and_keeps_the_slug(): void
    {
        $recipe = Recipe::where('slug', 'skin-tan')->with('steps.paints')->firstOrFail();
        $steps = $recipe->steps->map(fn ($step) => [
            'role' => $step->role,
            'usage' => $step->usage,
            'optional' => $step->optional ? '1' : '0',
            'technique' => $step->technique,
            'coverage' => $step->coverage,
            'paints' => $step->paints->map(fn ($paint) => ['paint_id' => $paint->id, 'drops' => $paint->pivot->drops])->all(),
        ])->reverse()->values()->all();

        $this->actingAs($this->admin())->put(route('resina.recipes.update', $recipe), [
            'title' => 'Pelle abbronzata rivista',
            'recipe_category_id' => $recipe->recipe_category_id,
            'steps' => $steps,
        ])->assertRedirect(route('resina.recipes.index').'#r-skin-tan');

        $recipe->refresh()->load('steps');
        $this->assertSame('skin-tan', $recipe->slug);
        $this->assertSame('Pelle abbronzata rivista', $recipe->title);
        $this->assertSame(array_column($steps, 'role'), $recipe->steps->pluck('role')->all());
    }

    public function test_the_same_paint_twice_in_a_step_adds_up_its_drops(): void
    {
        $data = $this->validData();
        $paintId = $data['steps'][0]['paints'][0]['paint_id'];
        $data['steps'][0]['paints'][] = ['paint_id' => $paintId, 'drops' => 2];

        $this->actingAs($this->admin())->post(route('resina.recipes.store'), $data)->assertRedirect();

        $shade = Recipe::where('slug', 'viola-profondo')->firstOrFail()->steps()->first();
        $this->assertSame(3, $shade->paints()->where('resin_paints.id', $paintId)->first()->pivot->drops);
    }

    public function test_a_recipe_needs_steps_with_paints(): void
    {
        $data = $this->validData();
        $data['steps'][1]['paints'] = [];

        $this->actingAs($this->admin())->post(route('resina.recipes.store'), $data)
            ->assertSessionHasErrors('steps.1.paints');

        $this->actingAs($this->admin())->post(route('resina.recipes.store'), [...$data, 'steps' => []])
            ->assertSessionHasErrors('steps');
    }

    public function test_a_recipe_in_use_cannot_be_deleted_but_an_unused_one_can(): void
    {
        $admin = $this->admin();
        $used = Recipe::where('slug', 'skin-tan')->firstOrFail();

        $this->actingAs($admin)->from(route('resina.recipes.edit', $used))
            ->delete(route('resina.recipes.destroy', $used))
            ->assertRedirect(route('resina.recipes.edit', $used))
            ->assertSessionHasErrors('recipe');
        $this->assertModelExists($used);

        $this->actingAs($admin)->post(route('resina.recipes.store'), $this->validData());
        $unused = Recipe::where('slug', 'viola-profondo')->firstOrFail();

        $this->actingAs($admin)->delete(route('resina.recipes.destroy', $unused))->assertRedirect(route('resina.recipes.index'));
        $this->assertModelMissing($unused);
    }

    public function test_inline_zone_recipes_are_not_editable_from_the_recipe_book(): void
    {
        $inline = Recipe::where('is_inline', true)->firstOrFail();

        $this->actingAs($this->admin())->get(route('resina.recipes.edit', $inline))->assertNotFound();
    }

    public function test_the_edit_form_carries_the_steps_for_the_editor(): void
    {
        $recipe = Recipe::where('slug', 'skin-tan')->firstOrFail();

        $response = $this->actingAs($this->admin())->get(route('resina.recipes.edit', $recipe))->assertOk();

        $payload = $this->payloadOf($response->getContent(), 'resina-recipe-editor');
        $this->assertSame($recipe->steps()->count(), count($payload['steps']));
        $this->assertContains('drybrush', $payload['techniques']);
    }

    /**
     * @return array<string, mixed>
     */
    private function validData(): array
    {
        $purple = Paint::where('code', '72.010')->value('id');
        $blue = Paint::where('code', '72.022')->value('id');
        $white = Paint::where('code', '72.001')->value('id');

        return [
            'title' => 'Viola profondo',
            'recipe_category_id' => RecipeCategory::where('slug', 'tessuti')->value('id'),
            'who' => 'Mantelli',
            'tip' => null,
            'steps' => [
                ['role' => 'Ombra', 'usage' => 'Negli incavi.', 'optional' => '0', 'technique' => 'sottile', 'coverage' => 35, 'paints' => [['paint_id' => $blue, 'drops' => 1]]],
                ['role' => 'Base', 'usage' => 'Su tutto.', 'optional' => '0', 'technique' => 'coprente', 'coverage' => 100, 'paints' => [['paint_id' => $purple, 'drops' => 2], ['paint_id' => $blue, 'drops' => 1]]],
                ['role' => 'Luce estrema', 'usage' => null, 'optional' => '1', 'technique' => '', 'coverage' => '', 'paints' => [['paint_id' => $white, 'drops' => 1]]],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadOf(string $html, string $id): array
    {
        preg_match('#<script type="application/json" id="'.$id.'">(.*?)</script>#s', $html, $match);

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
