<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\ArmorType;
use App\Models\Resina\Character;
use App\Models\Resina\Guide;
use App\Models\Resina\Paint;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaProjectTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_the_projects_page_lists_every_project(): void
    {
        $this->actingAs($this->painter())->get(route('resina.projects.index'))
            ->assertOk()
            ->assertSee('Saint Seiya')
            ->assertSee('Power Rangers')
            ->assertSee('Marvel')
            ->assertDontSee('Nuovo progetto');
    }

    public function test_every_tab_of_a_project_opens(): void
    {
        $painter = $this->painter();

        foreach (['personaggi', 'armature', 'passo', 'ricette', 'riferimenti'] as $tab) {
            $this->actingAs($painter)->get(route('resina.projects.show', ['saint-seiya', $tab]))->assertOk();
        }

        $this->actingAs($painter)->get(route('resina.projects.show', ['saint-seiya', 'riferimenti']))->assertSee('Seiyapedia (wiki)');
        $this->actingAs($painter)->get(route('resina.projects.show', ['saint-seiya', 'armature']))->assertSee('Cloth d\'oro');
    }

    public function test_tabs_without_content_send_back_to_the_characters(): void
    {
        $this->actingAs($this->painter())
            ->get(route('resina.projects.show', ['power-rangers', 'armature']))
            ->assertRedirect(route('resina.projects.show', 'power-rangers'));
    }

    public function test_the_characters_tab_carries_every_character_with_its_default_version(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.projects.show', 'saint-seiya'))->assertOk();
        $payload = $this->payloadOf($response->getContent(), 'resina-project');

        $this->assertCount(24, $payload['characters']);
        $this->assertCount(4, $payload['groups']);

        $seiya = Character::where('slug', 'seiya')->firstOrFail();
        $this->assertSame($seiya->versions()->orderBy('position')->value('id'), $payload['chosenVersions'][$seiya->id]);
    }

    public function test_the_project_recipes_include_the_extras_and_never_inline_ones(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.projects.show', ['saint-seiya', 'ricette']))->assertOk();
        $ids = $this->payloadOf($response->getContent(), 'resina-project')['projectRecipeIds'];

        $this->assertContains(Recipe::where('slug', 'fx-cosmo')->value('id'), $ids);
        $this->assertContains(Recipe::where('slug', 'metal-gold')->value('id'), $ids);
        $this->assertSame(0, Recipe::whereIn('id', $ids)->where('is_inline', true)->count());
        $this->assertSame(count($ids), count(array_unique($ids)));
    }

    public function test_painters_cannot_change_projects(): void
    {
        $painter = $this->painter();
        $project = Project::where('slug', 'marvel')->firstOrFail();

        $this->actingAs($painter)->get(route('resina.projects.create'))->assertForbidden();
        $this->actingAs($painter)->post(route('resina.projects.store'), ['name' => 'X', 'status' => 'completo'])->assertForbidden();
        $this->actingAs($painter)->get(route('resina.projects.edit', $project))->assertForbidden();
        $this->actingAs($painter)->delete(route('resina.projects.destroy', $project))->assertForbidden();
        $this->actingAs($painter)->get(route('resina.armor-types.create', 'saint-seiya'))->assertForbidden();
        $this->actingAs($painter)->get(route('resina.guides.create', 'saint-seiya'))->assertForbidden();
    }

    public function test_the_admin_creates_and_edits_a_project_with_groups_and_links(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('resina.projects.store'), [
            'name' => 'Dragon Ball',
            'status' => 'anteprima',
            'theme' => 't-mv',
            'default_bases' => ['base-rock'],
            'groups' => [['name' => 'Saiyan'], ['name' => 'Namecciani']],
            'links' => [['title' => 'Wiki', 'url' => 'https://dragonball.fandom.com']],
        ])->assertRedirect(route('resina.projects.show', 'dragon-ball'));

        $project = Project::where('slug', 'dragon-ball')->with('groups', 'links')->firstOrFail();
        $this->assertSame(['Saiyan', 'Namecciani'], $project->groups->pluck('name')->all());
        $this->assertSame(['base-rock'], $project->default_bases);

        [$saiyan, $namek] = $project->groups;
        $this->actingAs($admin)->put(route('resina.projects.update', $project), [
            'name' => 'Dragon Ball Z',
            'status' => 'completo',
            'groups' => [['id' => $namek->id, 'name' => 'Namek'], ['id' => $saiyan->id, 'name' => 'Saiyan']],
            'links' => [],
        ])->assertRedirect(route('resina.projects.show', 'dragon-ball'));

        $project->refresh()->load('groups', 'links');
        $this->assertSame('dragon-ball', $project->slug);
        $this->assertSame(['Namek', 'Saiyan'], $project->groups->pluck('name')->all());
        $this->assertCount(0, $project->links);
        $this->assertNull($project->default_bases);
    }

    public function test_deleting_a_project_takes_its_characters_and_their_inline_recipes(): void
    {
        $project = Project::where('slug', 'saint-seiya')->firstOrFail();
        $inline = Recipe::where('slug', 'inline-saint-seiya-seiya-base-3')->firstOrFail();

        $this->actingAs($this->admin())->delete(route('resina.projects.destroy', $project))->assertRedirect(route('resina.projects.index'));

        $this->assertModelMissing($project);
        $this->assertSame(0, Character::where('slug', 'seiya')->count());
        $this->assertModelMissing($inline);
        $this->assertModelExists(Recipe::where('slug', 'skin-tan')->firstOrFail());
    }

    public function test_the_admin_manages_armor_types_with_ordered_recipes(): void
    {
        $admin = $this->admin();
        $gold = Recipe::where('slug', 'metal-gold')->value('id');
        $silver = Recipe::where('slug', 'metal-silver')->value('id') ?? Recipe::catalog()->where('id', '!=', $gold)->value('id');
        $guide = Guide::where('slug', 'gold')->firstOrFail();

        $this->actingAs($admin)->post(route('resina.armor-types.store', 'saint-seiya'), [
            'title' => 'Cloth divina', 'position' => 10, 'guide_id' => $guide->id, 'recipes' => [$silver, $gold],
        ])->assertRedirect(route('resina.projects.show', ['saint-seiya', 'armature']));

        $armor = ArmorType::where('slug', 'cloth-divina')->firstOrFail();
        $this->assertSame([$silver, $gold], $armor->recipes->pluck('id')->all());

        $this->actingAs($admin)->put(route('resina.armor-types.update', ['saint-seiya', $armor]), [
            'title' => 'Cloth divina', 'position' => 10, 'recipes' => [$gold],
        ])->assertRedirect();
        $this->assertSame([$gold], $armor->refresh()->recipes->pluck('id')->all());
        $this->assertNull($armor->guide_id);

        $this->actingAs($admin)->delete(route('resina.armor-types.destroy', ['saint-seiya', $armor]))->assertRedirect();
        $this->assertModelMissing($armor);
    }

    public function test_the_admin_manages_guides_with_ordered_steps(): void
    {
        $admin = $this->admin();
        $black = Paint::where('code', '72.051')->value('id');

        $this->actingAs($admin)->post(route('resina.guides.store', 'saint-seiya'), [
            'title' => 'Cloth divina, passo passo',
            'position' => 9,
            'steps' => [
                ['title' => 'Primer nero', 'description' => 'Con la bomboletta.'],
                ['title' => 'Base', 'description' => 'Due mani.', 'paints' => [['paint_id' => $black, 'drops' => 2]]],
            ],
        ])->assertRedirect(route('resina.projects.show', ['saint-seiya', 'passo']));

        $guide = Guide::where('slug', 'cloth-divina-passo-passo')->with('steps.paints')->firstOrFail();
        $this->assertSame(['Primer nero', 'Base'], $guide->steps->pluck('title')->all());
        $this->assertSame(2, $guide->steps[1]->paints->first()->pivot->drops);

        $this->actingAs($admin)->put(route('resina.guides.update', ['saint-seiya', $guide]), [
            'title' => 'Cloth divina, passo passo',
            'position' => 9,
            'steps' => [
                ['title' => 'Base', 'paints' => [['paint_id' => $black, 'drops' => 1]]],
                ['title' => 'Primer nero'],
            ],
        ])->assertRedirect();

        $this->assertSame(['Base', 'Primer nero'], $guide->refresh()->steps->pluck('title')->all());
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
