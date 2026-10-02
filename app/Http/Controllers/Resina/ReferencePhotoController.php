<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterGroup;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Project;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\ReferencePhotos;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Reference photos of the catalog versions: the admin's checklist page,
 * the four ways of loading one (file, drag, paste, address — all ending
 * in a temporary token), attaching it to a version, and serving the
 * photos to everyone with access to the module.
 */
class ReferencePhotoController extends Controller
{
    public function __construct(private ReferencePhotos $photos, private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $this->authorizeAdmin($request);

        $projects = Project::with(['groups', 'characters.versions', 'characters.group'])->orderBy('position')->get();

        return view('resina.reference-photos.index', [
            'payload' => [
                'projects' => $projects->map(fn (Project $project) => [
                    'name' => $project->name,
                    'groups' => $project->groups->map(fn (CharacterGroup $group) => ['slug' => $group->slug, 'name' => $group->name])
                        ->push(['slug' => null, 'name' => 'Senza gruppo'])->values()->all(),
                    'characters' => $project->characters->map(fn (Character $character) => [
                        'name' => $character->name,
                        'group' => $character->group?->slug,
                        'href' => route('resina.characters.show', [$project, $character]),
                        'versions' => $character->versions->map(fn (CharacterVersion $version) => $this->payload->version($character, $version, $project))->all(),
                    ])->all(),
                ])->all(),
                'uploader' => self::uploaderEndpoints(),
            ],
        ]);
    }

    /**
     * Step one, the same for all four ways in: photo and thumbnail
     * already resized in the browser, or an address to download.
     */
    public function temporary(Request $request): JsonResponse
    {
        $this->authorizeAdmin($request);

        if ($request->filled('url')) {
            $data = $request->validate(['url' => 'required|string|max:2048']);

            try {
                $result = $this->photos->storeFromUrl($data['url']);
            } catch (RuntimeException $e) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return response()->json([
                'token' => $result['token'],
                'source' => $result['source'],
                // Set when the server couldn't resize: the browser does it and sends it back.
                'dataUrl' => $result['dataUrl'],
                'thumbUrl' => $result['token'] ? route('resina.references.temporary.image', $result['token']) : null,
            ]);
        }

        $data = $request->validate([
            'photo' => 'required|file|mimes:jpg,jpeg|mimetypes:image/jpeg|max:6144|dimensions:max_width=1600,max_height=1600',
            'thumb' => 'required|file|mimes:jpg,jpeg|mimetypes:image/jpeg|max:1024|dimensions:max_width=400,max_height=400',
            'source' => 'nullable|string|max:255',
        ], ['photo.required' => 'Manca la foto.']);

        $token = $this->photos->storeUploaded($request->file('photo'), $request->file('thumb'), $data['source'] ?? null);

        return response()->json([
            'token' => $token,
            'source' => $data['source'] ?? null,
            'dataUrl' => null,
            'thumbUrl' => route('resina.references.temporary.image', $token),
        ]);
    }

    public function temporaryImage(Request $request, string $token): StreamedResponse
    {
        $this->authorizeAdmin($request);
        abort_unless($this->photos->temporaryExists($token), 404);

        return Storage::disk('local')->response($this->photos->tempPath($token, true), null, ['Cache-Control' => 'private, no-store']);
    }

    public function attach(Request $request, CharacterVersion $version): JsonResponse
    {
        $this->authorizeAdmin($request);
        abort_if($version->character->isPersonal(), 404);

        $data = $request->validate(['token' => 'required|uuid', 'source' => 'nullable|string|max:255']);

        try {
            $this->photos->attach($version, $data['token'], $data['source'] ?? null);
        } catch (RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        $character = $version->character()->with('project')->first();

        return response()->json($this->payload->version($character, $version->refresh(), $character->project));
    }

    public function updateSource(Request $request, CharacterVersion $version): JsonResponse
    {
        $this->authorizeAdmin($request);

        $data = $request->validate(['source' => 'nullable|string|max:255']);
        $version->update(['reference_source' => $data['source'] ?? null]);

        return response()->json(['source' => $version->reference_source]);
    }

    /**
     * Catalog photos: everyone with access to the module sees them.
     */
    public function show(CharacterVersion $version, string $variant): StreamedResponse
    {
        $path = $variant === 'miniatura' ? $version->reference_thumb_path : $version->reference_image_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=604800']);
    }

    /**
     * @return array{temporary: string}
     */
    public static function uploaderEndpoints(): array
    {
        return ['temporary' => route('resina.references.temporary')];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
