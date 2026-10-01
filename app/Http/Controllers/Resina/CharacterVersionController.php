<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\Zone;
use App\Services\Resina\ZoneSync;
use App\Support\ResinaSlug;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * A character's versions (anime V1, manga, …), admin only. A version's
 * zones replace the shared zones with the same name and add to the rest.
 */
class CharacterVersionController extends Controller
{
    public function __construct(private ZoneSync $zoneSync) {}

    public function create(Request $request, Project $project, Character $character): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, $character, new CharacterVersion(['position' => ($character->versions()->max('position') ?? 0) + 1]));
    }

    public function store(Request $request, Project $project, Character $character): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateVersion($request);

        DB::transaction(function () use ($character, $data) {
            $version = $character->versions()->create([
                ...$this->attributes($data),
                'slug' => ResinaSlug::unique($character->versions(), $data['label'], 'versione'),
            ]);
            $this->zoneSync->sync($character, $version, $data['zones'] ?? []);
        });

        return redirect()->route('resina.characters.edit', [$project, $character])->with('status', 'Versione aggiunta.');
    }

    public function edit(Request $request, Project $project, Character $character, CharacterVersion $version): View
    {
        $this->authorizeAdmin($request);

        return $this->form($project, $character, $version);
    }

    public function update(Request $request, Project $project, Character $character, CharacterVersion $version): RedirectResponse
    {
        $this->authorizeAdmin($request);

        $data = $this->validateVersion($request);

        DB::transaction(function () use ($character, $version, $data) {
            $version->update($this->attributes($data));
            $this->zoneSync->sync($character, $version, $data['zones'] ?? []);
        });

        return redirect()->route('resina.characters.edit', [$project, $character])->with('status', 'Versione aggiornata.');
    }

    /**
     * Its zones go with it; users who had picked it fall back to the
     * first version.
     */
    public function destroy(Request $request, Project $project, Character $character, CharacterVersion $version): RedirectResponse
    {
        $this->authorizeAdmin($request);

        DB::transaction(function () use ($version) {
            $version->delete();
            Recipe::deleteOrphanInline();
        });

        return redirect()->route('resina.characters.edit', [$project, $character])->with('status', "Versione «{$version->label}» eliminata.");
    }

    private function form(Project $project, Character $character, CharacterVersion $version): View
    {
        $zones = $version->exists ? $version->zones()->with('recipe')->get() : collect();

        return view('resina.characters.version-form', [
            'project' => $project,
            'character' => $character,
            'version' => $version,
            'zoneEditor' => CharacterController::zoneEditorPayload(old('zones', $zones->map(fn (Zone $zone) => CharacterController::zoneRow($zone))->all()), $zones),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validateVersion(Request $request): array
    {
        return $request->validate([
            'label' => 'required|string|max:100',
            'subtitle' => 'nullable|string|max:255',
            'note' => 'nullable|string|max:2000',
            'position' => 'required|integer|min:1|max:999',
            ...CharacterController::zoneRules(),
        ], CharacterController::zoneMessages());
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data): array
    {
        return [
            'label' => $data['label'],
            'subtitle' => $data['subtitle'] ?? null,
            'note' => $data['note'] ?? null,
            'position' => $data['position'],
        ];
    }

    private function authorizeAdmin(Request $request): void
    {
        abort_unless($request->user()->hasRole('admin'), 403);
    }
}
