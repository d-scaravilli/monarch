<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Project;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\FigureBuilder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "Analizza una foto". Everything visual happens in the browser: the
 * photo is resized there (JPEG, longest side ≤ 1600 px) with a
 * thumbnail, colors are extracted and sampled there. The server gets
 * the two JPEGs, the chosen character or the new one's details, and the
 * colors assigned to zones, and builds the figure.
 */
class PhotoAnalysisController extends Controller
{
    public function __construct(private ClientPayload $payload, private FigureBuilder $builder) {}

    public function create(Request $request): View
    {
        return view('resina.photo.create', [
            'payload' => [
                'paints' => $this->payload->paints($request->user()),
                'projects' => Project::with(['characters.versions'])->orderBy('position')->get()->map(fn (Project $project) => [
                    'name' => $project->name,
                    'characters' => $project->characters->map(fn (Character $character) => [
                        'id' => $character->id,
                        'name' => $character->name,
                        'subtitle' => $character->subtitle,
                        'href' => route('resina.characters.show', [$project, $character]),
                        'versions' => $character->versions->map(fn (CharacterVersion $version) => [
                            'id' => $version->id,
                            'label' => $version->label.($version->subtitle ? ' · '.$version->subtitle : ''),
                        ])->values()->all(),
                    ])->values()->all(),
                ])->all(),
                'storeUrl' => route('resina.photo.store'),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            // Always JPEG: the browser re-encodes whatever was picked.
            'photo' => 'required|file|mimes:jpg,jpeg|mimetypes:image/jpeg|max:6144|dimensions:max_width=1600,max_height=1600',
            'thumb' => 'required|file|mimes:jpg,jpeg|mimetypes:image/jpeg|max:512|dimensions:max_width=400,max_height=400',
            'character_id' => ['nullable', 'integer', Rule::exists('resin_characters', 'id')->whereNull('user_id')],
            'version_id' => ['nullable', 'integer', Rule::exists('resin_character_versions', 'id')->where('character_id', $request->integer('character_id'))],
            'name' => 'nullable|string|max:255',
            'series' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:2000',
            'colors' => 'nullable|array|max:30',
            'colors.*.hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'colors.*.tab' => ['required', Rule::in(array_keys(FigureBuilder::PHOTO_ZONE_LABELS))],
            'colors.*.name' => 'nullable|string|max:100',
        ], [
            'photo.required' => 'Carica prima un\'immagine.',
            'photo.mimes' => 'La foto deve arrivare come JPEG.',
            'photo.max' => 'La foto è troppo grande.',
        ]);

        $user = $request->user();
        $character = isset($data['character_id']) ? Character::find($data['character_id']) : null;
        $version = isset($data['version_id']) ? CharacterVersion::find($data['version_id']) : null;

        $folder = 'resina/figures/'.$user->id;
        $base = Str::uuid()->toString();
        $images = [
            'photo' => $request->file('photo')->storeAs($folder, $base.'.jpg', 'local'),
            'thumb' => $request->file('thumb')->storeAs($folder, $base.'-miniatura.jpg', 'local'),
        ];

        $figure = $this->builder->fromPhoto($user, [
            'character' => $character,
            'version' => $version,
            'name' => $data['name'] ?? null,
            'series' => $data['series'] ?? null,
            'notes' => $data['notes'] ?? null,
        ], $data['colors'] ?? [], $images);

        if (! $figure) {
            Storage::disk('local')->delete(array_values($images));

            return response()->json(['message' => 'Assegna almeno un colore a una zona, oppure scegli un personaggio.'], 422);
        }

        return response()->json(['redirect' => route('resina.figures.show', $figure)]);
    }
}
