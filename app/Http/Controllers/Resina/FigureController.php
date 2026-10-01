<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\Recipe;
use App\Models\Resina\Zone;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\FigureBuilder;
use App\Services\Resina\ZoneSync;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * "Le mie figure": personal characters, each user sees and edits only
 * their own. They use the same tabbed sheet as catalog characters.
 */
class FigureController extends Controller
{
    public function __construct(private ClientPayload $payload, private ZoneSync $zoneSync, private FigureBuilder $builder) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $figures = $user->resinFigures()->with('zones')->latest()->get();

        return view('resina.figures.index', [
            'figures' => $figures,
            'payload' => [
                'paints' => $this->payload->paints($user),
                'figures' => $figures->map(fn (Character $figure) => [
                    'id' => $figure->id,
                    'zones' => $figure->zones->sortBy('position')->take(6)->map(fn (Zone $zone) => $this->payload->zone($zone))->values()->all(),
                ])->all(),
                'recipes' => $this->payload->recipesById($figures->flatMap->zones->pluck('recipe_id')),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate(['name' => 'required|string|max:255'], ['name.required' => 'Scrivi il nome della figura.']);

        $figure = $this->builder->blank($request->user(), $data['name']);

        return redirect()->route('resina.figures.show', $figure)->with('status', 'Scheda creata: aggiungi le zone.');
    }

    public function show(Request $request, Character $figure, string $tab = 'panoramica'): View
    {
        $this->authorizeOwner($request, $figure);
        $figure->load(['zones.recipe', 'versions']);

        $zones = $figure->zones->sortBy('position')->values();

        return view('resina.figures.show', [
            'figure' => $figure,
            'payload' => $this->payload->characterSheet($request->user(), $figure, null, [
                'tab' => in_array($tab, CharacterController::TABS, true) ? $tab : 'panoramica',
                'baseUrl' => route('resina.figures.show', $figure),
                'copyUrl' => null,
            ]),
            'zoneEditor' => CharacterController::zoneEditorPayload(
                old('zones', $zones->map(fn (Zone $zone) => CharacterController::zoneRow($zone))->all()),
                $zones,
                canEditInline: false,
            ),
            'openEditor' => $zones->isEmpty() || session()->has('errors') || session('status') === 'Scheda creata: aggiungi le zone.',
        ]);
    }

    public function update(Request $request, Character $figure): RedirectResponse
    {
        $this->authorizeOwner($request, $figure);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            ...CharacterController::zoneRules(),
        ], CharacterController::zoneMessages());

        DB::transaction(function () use ($figure, $data) {
            $figure->update(['name' => $data['name']]);
            $this->zoneSync->sync($figure, null, $data['zones'] ?? []);
        });

        return redirect()->route('resina.figures.show', $figure)->with('status', 'Scheda salvata.');
    }

    /**
     * The figure, its zones, progress and own inline steps go, and its
     * photos with them.
     */
    public function destroy(Request $request, Character $figure): RedirectResponse
    {
        $this->authorizeOwner($request, $figure);

        DB::transaction(function () use ($figure) {
            $figure->delete();
            Recipe::deleteOrphanInline();
        });

        Storage::disk('local')->delete(array_filter([$figure->reference_image_path, $figure->reference_thumb_path]));

        return redirect()->route('resina.figures.index')->with('status', "«{$figure->name}» eliminata.");
    }

    /**
     * «Copia nelle mie figure» from a catalog character, in the version
     * the user has chosen for it.
     */
    public function copy(Request $request, Character $character): RedirectResponse
    {
        abort_if($character->isPersonal(), 404);

        $character->load(['zones.recipe', 'versions']);
        $versionId = $this->payload->chosenVersions($request->user(), collect([$character]))[$character->id];

        $figure = $this->builder->copy($request->user(), $character, $character->versions->firstWhere('id', $versionId));

        return redirect()->route('resina.figures.show', $figure)->with('status', 'Copiata nelle tue figure: ora puoi personalizzarla.');
    }

    /**
     * The reference photo or its thumbnail. Photos are personal: they
     * live on the private disk and only their owner gets them.
     */
    public function image(Request $request, Character $figure, string $variant): StreamedResponse
    {
        $this->authorizeOwner($request, $figure);

        $path = $variant === 'miniatura' ? $figure->reference_thumb_path : $figure->reference_image_path;
        abort_unless($path && Storage::disk('local')->exists($path), 404);

        return Storage::disk('local')->response($path, null, ['Cache-Control' => 'private, max-age=86400']);
    }

    private function authorizeOwner(Request $request, Character $figure): void
    {
        abort_unless($figure->user_id !== null && $figure->user_id === $request->user()->id, 404);
    }
}
