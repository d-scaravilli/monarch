<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Paint;
use App\Models\Resina\PathStep;
use App\Models\Resina\SavedMix;
use App\Models\Resina\ShopSuggestion;
use App\Models\Resina\Tutorial;
use App\Models\Resina\UserPaint;
use App\Models\User;
use App\Services\Resina\StarterKit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaToolsTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'admin']);
        $this->module = Module::where('slug', 'resina')->firstOrFail();
    }

    public function test_the_finder_offers_quick_colors_with_character_names(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.finder', ['hex' => '#c89b35']))->assertOk();
        $payload = $this->payloadOf($response->getContent(), 'resina-finder');

        $labels = array_column($payload['presets'], 'label');
        $this->assertSame('Pelle anime', $labels[0]);
        $this->assertContains('Seiya · capelli', $labels);
        $this->assertSame('Marmo', end($labels));
        $this->assertSame('#C89B35', $payload['initialHex']);
        $this->assertSame(route('resina.mixer'), $payload['urls']['mixer']);
    }

    public function test_step_rows_now_link_to_the_mixer_and_the_techniques(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.recipes.index'))->assertOk();
        $urls = $this->payloadOf($response->getContent(), 'resina-recipes')['urls'];

        $this->assertSame(route('resina.mixer'), $urls['mixer']);
        $this->assertSame(route('resina.techniques.index'), $urls['techniques']);
    }

    public function test_the_techniques_page_has_an_anchor_for_every_technique(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.techniques.index'))->assertOk();

        foreach (['t-prep', 't-diluire', 't-bsl', 't-tmm', 't-wash', 't-drybrush', 't-velatura', 't-spigoli', 't-occhi', 't-vernice', 't-errori'] as $anchor) {
            $response->assertSee('id="'.$anchor.'"', false);
        }
    }

    public function test_tutorials_open_youtube_searches_in_a_new_tab(): void
    {
        $tutorial = Tutorial::orderBy('position')->firstOrFail();

        $this->actingAs($this->painter())->get(route('resina.tutorials.index'))
            ->assertOk()
            ->assertSee('https://www.youtube.com/results?search_query='.urlencode($tutorial->query), false)
            ->assertSee('target="_blank"', false);
    }

    public function test_the_shop_lists_the_essentials_and_the_suggested_paints(): void
    {
        $response = $this->actingAs($this->painter())->get(route('resina.shop.index'))
            ->assertOk()
            ->assertSee('Indispensabili')
            ->assertSee('73.200')
            ->assertSee('Molto utili');

        $this->assertCount(12, $this->payloadOf($response->getContent(), 'resina-shop')['suggestions']);
    }

    public function test_the_mixer_state_is_saved_cleaned_up(): void
    {
        $painter = $this->painter();
        $mine = UserPaint::factory()->create(['user_id' => $painter->id]);
        $theirs = UserPaint::factory()->create();
        $paint = Paint::where('code', '72.001')->value('id');

        $this->actingAs($painter)->putJson(route('resina.mixer.state'), ['mix' => [
            'p'.$paint => 90, 'u'.$mine->id => 2, 'u'.$theirs->id => 1, 'p999999' => 1, 'hack' => 3, 'p'.($paint + 1) => 0,
        ]])->assertOk();

        $this->assertSame(['p'.$paint => 40, 'u'.$mine->id => 2], $painter->resinProfile()->first()->mixer);

        $payload = $this->payloadOf($this->actingAs($painter)->get(route('resina.mixer'))->getContent(), 'resina-mixer');
        $this->assertSame(['p'.$paint => 40, 'u'.$mine->id => 2], $payload['mixer']);
    }

    public function test_saved_mixes_are_kept_per_user(): void
    {
        $painter = $this->painter();
        $paint = Paint::where('code', '72.001')->value('id');

        $this->actingAs($painter)->postJson(route('resina.mixes.store'), ['name' => 'Pelle chiara', 'mix' => ['p'.$paint => 2]])
            ->assertOk()
            ->assertJsonPath('saved.0.name', 'Pelle chiara');

        $mix = SavedMix::firstOrFail();
        $this->actingAs($this->painter())->deleteJson(route('resina.mixes.destroy', $mix))->assertNotFound();
        $this->actingAs($painter)->postJson(route('resina.mixes.store'), ['mix' => ['nope' => 1]])->assertStatus(422);

        $this->actingAs($painter)->deleteJson(route('resina.mixes.destroy', $mix))->assertOk()->assertJsonCount(0, 'saved');
    }

    public function test_path_steps_are_ticked_per_user_and_show_on_the_home(): void
    {
        $painter = $this->painter();
        $first = PathStep::where('position', 1)->firstOrFail();

        $this->actingAs($painter)->postJson(route('resina.path.toggle', $first), ['done' => true])->assertOk();
        $this->actingAs($painter)->postJson(route('resina.path.toggle', $first), ['done' => true])->assertOk();

        $this->actingAs($painter)->get(route('resina.home'))->assertSee('1 di 11 passi fatti')->assertSee('Continua il percorso');
        $this->actingAs($this->painter())->get(route('resina.home'))->assertSee('0 di 11 passi fatti')->assertSee('Inizia il percorso');

        $this->actingAs($painter)->postJson(route('resina.path.toggle', $first), ['done' => false])->assertOk();
        $this->assertSame(0, $painter->resinCompletedPathSteps()->count());
    }

    public function test_the_path_page_links_each_step_to_how_its_done(): void
    {
        $this->actingAs($this->painter())->get(route('resina.path.index'))
            ->assertOk()
            ->assertSee(route('resina.techniques.index').'#t-errori', false)
            ->assertSee(route('resina.brushes.index'), false)
            ->assertSee('Ordine per dipingere una figura');
    }

    public function test_the_admin_edits_the_path(): void
    {
        $steps = PathStep::orderBy('position')->get();
        $done = $steps[2];
        $painter = $this->painter();
        $painter->resinCompletedPathSteps()->attach($steps[0]->id, ['done_at' => now()]);

        $this->actingAs($this->admin())->put(route('resina.path.update'), ['steps' => [
            ['id' => $done->id, 'title' => 'Primer prima di tutto', 'link_route' => 'resina.techniques.index', 'link_anchor' => 't-primer'],
            ['id' => $steps[1]->id, 'title' => $steps[1]->title, 'link_route' => 'resina.finder', 'link_anchor' => 't-prep'],
            ['title' => 'Passo nuovo'],
        ]])->assertRedirect(route('resina.path.index'));

        $this->assertSame(['Primer prima di tutto', $steps[1]->title, 'Passo nuovo'], PathStep::orderBy('position')->pluck('title')->all());
        $this->assertNull(PathStep::where('position', 2)->value('link_anchor'));
        $this->assertSame(0, $painter->resinCompletedPathSteps()->count());
    }

    public function test_the_admin_edits_tutorials_and_suggested_paints(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('resina.tutorials.update'), ['tutorials' => [
            ['title' => 'Occhi', 'query' => 'occhi miniature'],
        ]])->assertRedirect(route('resina.tutorials.index'));
        $this->assertSame(['Occhi'], Tutorial::pluck('title')->all());

        $keep = ShopSuggestion::where('code', '72.016')->firstOrFail();
        $this->actingAs($admin)->put(route('resina.shop.update'), ['suggestions' => [
            ['title' => 'x', 'code' => '72.999', 'name' => 'Nuovo', 'hex' => '#123456'],
            ['id' => $keep->id, 'code' => '72.016', 'name' => 'Royal Purple', 'hex' => '#5B3A86'],
        ]])->assertRedirect(route('resina.shop.index'));
        $this->assertSame(['72.999', '72.016'], ShopSuggestion::orderBy('position')->pluck('code')->all());

        $this->actingAs($admin)->put(route('resina.shop.update'), ['suggestions' => [
            ['code' => '72.1', 'name' => 'A', 'hex' => '#111111'],
            ['code' => '72.1', 'name' => 'B', 'hex' => '#222222'],
        ]])->assertSessionHasErrors('suggestions.1.code');
    }

    public function test_painters_cannot_edit_path_tutorials_or_suggestions(): void
    {
        $painter = $this->painter();

        foreach (['resina.path.edit', 'resina.tutorials.edit', 'resina.shop.edit'] as $route) {
            $this->actingAs($painter)->get(route($route))->assertForbidden();
        }
        $this->actingAs($painter)->put(route('resina.path.update'), ['steps' => []])->assertForbidden();
        $this->assertSame(11, PathStep::count());
    }

    public function test_the_phone_bar_keeps_four_pages_and_moves_the_rest_to_more(): void
    {
        $this->actingAs($this->admin())->get(route('resina.home'))
            ->assertOk()
            ->assertSee('Altro')
            ->assertSee('Il tuo kit')
            ->assertSee('Imparare');
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
        app(StarterKit::class)->ensureFor($painter);

        return $painter;
    }
}
