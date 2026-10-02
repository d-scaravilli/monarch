<?php

namespace Tests\Feature;

use App\Models\Module;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Zone;
use App\Models\User;
use App\Services\Resina\RemoteImageFetcher;
use App\Services\Resina\StarterKit;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ResinaReferencePhotoTest extends TestCase
{
    use RefreshDatabase;

    private Module $module;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
        Role::create(['name' => 'admin']);
        $this->module = Module::where('slug', 'resina')->firstOrFail();

        // Host names resolve without the network: example hosts are public, "intranet.example" is private.
        $this->app->bind(RemoteImageFetcher::class, fn () => new class extends RemoteImageFetcher
        {
            protected function resolve(string $host): array
            {
                return $host === 'intranet.example' ? ['192.168.1.10'] : ['93.184.216.34'];
            }
        });
    }

    public function test_characters_without_versions_get_unica_and_keep_their_zones(): void
    {
        $shaka = Character::where('slug', 'shaka')->with('versions')->firstOrFail();
        $seiya = Character::where('slug', 'seiya')->with('versions')->firstOrFail();

        $this->assertSame(['unica'], $shaka->versions->pluck('slug')->all());
        $this->assertSame(6, $seiya->versions->count());
        $this->assertSame(0, Character::catalog()->doesntHave('versions')->count());
        // Zones don't move: saved progress ("z{id}|n") keeps pointing to the same rows.
        $this->assertSame(203, Zone::count());
        $this->assertSame(0, Zone::whereIn('character_version_id', CharacterVersion::where('slug', 'unica')->pluck('id'))->count());
    }

    public function test_a_new_character_gets_unica_and_the_last_version_cannot_be_deleted(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('resina.characters.store', 'marvel'), ['name' => 'Loki'])->assertRedirect();

        $loki = Character::where('slug', 'loki')->firstOrFail();
        $only = $loki->versions()->sole();
        $this->assertSame('Unica', $only->label);

        $this->actingAs($admin)->delete(route('resina.versions.destroy', ['marvel', 'loki', $only->slug]))->assertSessionHasErrors('version');
        $this->assertModelExists($only);
    }

    public function test_uploading_then_attaching_a_photo_serves_it_to_module_users_only(): void
    {
        $admin = $this->admin();
        $version = $this->seiyaVersion('a1');

        $upload = $this->actingAs($admin)->post(route('resina.references.temporary'), [
            'photo' => UploadedFile::fake()->image('foto.jpg', 1600, 1200),
            'thumb' => UploadedFile::fake()->image('m.jpg', 400, 300),
        ], ['Accept' => 'application/json'])->assertOk();

        $this->actingAs($admin)->get($upload->json('thumbUrl'))->assertOk();

        $this->actingAs($admin)->putJson(route('resina.references.attach', $version), ['token' => $upload->json('token'), 'source' => 'saintseiya.fandom.com'])
            ->assertOk()
            ->assertJsonPath('source', 'saintseiya.fandom.com')
            ->assertJsonPath('id', $version->id);

        $version->refresh();
        $this->assertTrue($version->hasReferencePhoto());
        Storage::disk('local')->assertExists([$version->reference_image_path, $version->reference_thumb_path]);
        $this->assertSame([], Storage::disk('local')->files('resina/references/tmp'));

        $painter = $this->painter();
        $this->actingAs($painter)->get(route('resina.references.show', [$version, 'originale']))->assertOk()->assertHeader('Content-Type', 'image/jpeg');
        $this->actingAs(User::factory()->create())->get(route('resina.references.show', [$version, 'miniatura']))->assertForbidden();
    }

    public function test_replacing_a_photo_removes_the_old_files(): void
    {
        $admin = $this->admin();
        $version = $this->seiyaVersion('a1');

        $this->attachFake($admin, $version);
        $old = [$version->refresh()->reference_image_path, $version->reference_thumb_path];
        $this->attachFake($admin, $version);

        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($version->refresh()->reference_image_path);
    }

    public function test_only_the_admin_loads_photos_and_sees_the_checklist(): void
    {
        $painter = $this->painter();
        $version = $this->seiyaVersion('a1');

        $this->actingAs($painter)->get(route('resina.references.index'))->assertForbidden();
        $this->actingAs($painter)->post(route('resina.references.temporary'), ['url' => 'https://images.example.com/a.jpg'], ['Accept' => 'application/json'])->assertForbidden();
        $this->actingAs($painter)->putJson(route('resina.references.attach', $version), ['token' => (string) str()->uuid()])->assertForbidden();
        $this->actingAs($painter)->patchJson(route('resina.references.source', $version), ['source' => 'x'])->assertForbidden();

        $response = $this->actingAs($this->admin())->get(route('resina.references.index'))->assertOk()->assertSee('Foto di riferimento');
        preg_match('#<script type="application/json" id="resina-references">(.*?)</script>#s', $response->getContent(), $match);
        $versions = collect(json_decode($match[1], true)['projects'])->flatMap(fn ($p) => collect($p['characters'])->flatMap(fn ($c) => $c['versions']));
        $this->assertCount(59, $versions);
        $this->assertSame(59, $versions->whereNull('photo')->count());
    }

    public function test_the_home_reminds_only_the_admin_about_missing_photos(): void
    {
        $this->actingAs($this->admin())->get(route('resina.home'))->assertSee('59 foto di riferimento da aggiungere');
        $this->actingAs($this->painter())->get(route('resina.home'))->assertDontSee('foto di riferimento da aggiungere');
    }

    public function test_an_image_address_is_downloaded_resized_and_its_domain_becomes_the_source(): void
    {
        Http::fake(['images.example.com/*' => Http::response($this->pngBytes(2400, 1800), 200, ['Content-Type' => 'image/png'])]);

        $response = $this->actingAs($this->admin())->post(route('resina.references.temporary'), ['url' => 'https://www.images.example.com/seiya.png'], ['Accept' => 'application/json'])
            ->assertOk()
            ->assertJsonPath('source', 'images.example.com')
            ->assertJsonPath('dataUrl', null);

        $token = $response->json('token');
        [$width, $height] = getimagesizefromstring(Storage::disk('local')->get("resina/references/tmp/{$token}.jpg"));
        [$thumbWidth] = getimagesizefromstring(Storage::disk('local')->get("resina/references/tmp/{$token}-miniatura.jpg"));
        $this->assertSame([1600, 1200, 400], [$width, $height, $thumbWidth]);
    }

    public function test_unsafe_addresses_are_refused_without_any_request(): void
    {
        Http::fake();
        $admin = $this->admin();

        foreach ([
            'http://localhost/a.jpg',
            'http://127.0.0.1/a.jpg',
            'http://10.0.0.5/a.jpg',
            'http://169.254.169.254/latest/meta-data',
            'http://[::1]/a.jpg',
            'http://intranet.example/a.jpg',
            'file:///etc/passwd',
            'ftp://images.example.com/a.jpg',
            'https://user:pass@images.example.com/a.jpg',
        ] as $url) {
            $this->actingAs($admin)->post(route('resina.references.temporary'), ['url' => $url], ['Accept' => 'application/json'])
                ->assertStatus(422);
        }

        Http::assertNothingSent();
    }

    public function test_redirects_to_private_addresses_wrong_types_and_huge_files_are_refused(): void
    {
        Http::fake([
            'images.example.com/redirect' => Http::response('', 302, ['Location' => 'http://192.168.0.1/admin.png']),
            'images.example.com/page' => Http::response('<html></html>', 200, ['Content-Type' => 'text/html']),
            'images.example.com/huge' => Http::response('x', 200, ['Content-Type' => 'image/jpeg', 'Content-Length' => (string) (11 * 1024 * 1024)]),
        ]);
        $admin = $this->admin();

        foreach (['redirect' => 'non è consentito', 'page' => 'non porta a un\'immagine', 'huge' => '10 MB'] as $path => $message) {
            $this->actingAs($admin)->post(route('resina.references.temporary'), ['url' => "https://images.example.com/{$path}"], ['Accept' => 'application/json'])
                ->assertStatus(422)
                ->assertJsonFragment(['message' => collect(['redirect' => 'Questo indirizzo non è consentito.', 'page' => 'L\'indirizzo non porta a un\'immagine: con il tasto destro scegli «Copia indirizzo immagine», non quello della pagina.', 'huge' => 'L\'immagine supera i 10 MB.'])[$path]]);
        }
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_a_new_version_needs_a_photo_and_keeps_it(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('resina.versions.store', ['marvel', 'thor']), ['label' => 'Ragnarok', 'position' => 2])
            ->assertSessionHasErrors('reference_token');

        $this->actingAs($admin)->post(route('resina.versions.store', ['marvel', 'thor']), ['label' => 'Ragnarok', 'position' => 2, 'reference_token' => (string) str()->uuid()])
            ->assertSessionHasErrors('reference_token');

        $token = $this->fakeUpload($admin);
        $this->actingAs($admin)->post(route('resina.versions.store', ['marvel', 'thor']), ['label' => 'Ragnarok', 'position' => 2, 'reference_token' => $token, 'reference_source' => 'mia foto'])
            ->assertRedirect();

        $version = Character::where('slug', 'thor')->firstOrFail()->versions()->where('slug', 'ragnarok')->firstOrFail();
        $this->assertTrue($version->hasReferencePhoto());
        $this->assertSame('mia foto', $version->reference_source);
    }

    public function test_deleting_a_version_or_a_character_removes_their_photos(): void
    {
        $admin = $this->admin();
        $a1 = $this->seiyaVersion('a1');
        $a2 = $this->seiyaVersion('a2');
        $this->attachFake($admin, $a1);
        $this->attachFake($admin, $a2);

        $a1Photo = $a1->refresh()->reference_image_path;

        $this->actingAs($admin)->delete(route('resina.versions.destroy', ['saint-seiya', 'seiya', 'a1']))->assertRedirect();
        Storage::disk('local')->assertMissing($a1Photo);

        $this->actingAs($admin)->delete(route('resina.characters.destroy', ['saint-seiya', 'seiya']))->assertRedirect();
        $this->assertSame([], Storage::disk('local')->allFiles('resina/references'));
    }

    public function test_the_character_sheet_knows_each_versions_photo_and_who_can_load_it(): void
    {
        $admin = $this->admin();
        $this->attachFake($admin, $this->seiyaVersion('a1'));

        $payload = fn (User $user) => $this->sheetPayload($user, ['saint-seiya', 'seiya']);
        $versions = collect($payload($admin)['character']['versions']);

        $this->assertNotNull($versions->firstWhere('slug', 'a1')['photo']);
        $this->assertNull($versions->firstWhere('slug', 'a2')['photo']);
        $this->assertTrue($payload($admin)['canUploadReferences']);
        $this->assertFalse($payload($this->painter())['canUploadReferences']);
    }

    public function test_search_links_use_the_projects_armor_name_and_skip_unica(): void
    {
        $seiya = collect($this->sheetPayload($this->admin(), ['saint-seiya', 'seiya'])['character']['versions'])->firstWhere('slug', 'a1');
        $ironman = collect($this->sheetPayload($this->admin(), ['marvel', 'ironman'])['character']['versions'])->first();

        $this->assertSame('Pegasus Seiya anime V1 cloth', $seiya['searchQuery']);
        $this->assertStringNotContainsString('cloth', $ironman['searchQuery']);
        $this->assertStringNotContainsString('Unica', $ironman['searchQuery']);
    }

    public function test_reimporting_the_catalog_keeps_the_photos(): void
    {
        $admin = $this->admin();
        $version = $this->seiyaVersion('a1');
        $this->attachFake($admin, $version);

        $this->actingAs($admin)->post(route('modules.settings.reimport', $this->module), ['confirm_name' => '3D - Resina'])->assertRedirect();

        $this->assertTrue($version->refresh()->hasReferencePhoto());
        $this->assertSame(59, CharacterVersion::count());
    }

    private function seiyaVersion(string $slug): CharacterVersion
    {
        return Character::where('slug', 'seiya')->firstOrFail()->versions()->where('slug', $slug)->firstOrFail();
    }

    private function fakeUpload(User $admin): string
    {
        return $this->actingAs($admin)->post(route('resina.references.temporary'), [
            'photo' => UploadedFile::fake()->image('foto.jpg', 800, 600),
            'thumb' => UploadedFile::fake()->image('m.jpg', 400, 300),
        ], ['Accept' => 'application/json'])->json('token');
    }

    private function attachFake(User $admin, CharacterVersion $version): void
    {
        $this->actingAs($admin)->putJson(route('resina.references.attach', $version), ['token' => $this->fakeUpload($admin)])->assertOk();
    }

    /**
     * @param  array<int, string>  $route
     * @return array<string, mixed>
     */
    private function sheetPayload(User $user, array $route): array
    {
        $html = $this->actingAs($user)->get(route('resina.characters.show', $route))->assertOk()->getContent();
        preg_match('#<script type="application/json" id="resina-character">(.*?)</script>#s', $html, $match);

        return json_decode($match[1], true);
    }

    private function pngBytes(int $width, int $height): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 200, 60, 50));
        ob_start();
        imagepng($image);

        return (string) ob_get_clean();
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
