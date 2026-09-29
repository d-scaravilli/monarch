<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use App\Models\Resina\Zone;
use App\Services\Resina\ClientPayload;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * "Trova un colore": the search itself runs in the browser (mix worker),
 * over the paints the user owns.
 */
class FinderController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('resina.finder', [
            'payload' => [
                'paints' => $this->payload->paints($user),
                'suggestions' => $this->payload->shopSuggestions($user),
                'urls' => $this->payload->urls(),
                'presets' => $this->presets(),
                'initialHex' => preg_match('/^#[0-9a-fA-F]{6}$/', (string) $request->query('hex')) ? strtoupper($request->query('hex')) : '#D9A07A',
            ],
        ]);
    }

    /**
     * Quick colors: skin and gold, every reference color of the catalog
     * characters' shared zones (with the character's name), then earth,
     * rock and marble — as in the prototype.
     *
     * @return array<int, array{label: string, hex: string}>
     */
    private function presets(): array
    {
        $zones = Zone::query()
            ->whereNull('character_version_id')
            ->whereNotNull('target_hex')
            ->whereHas('character', fn ($q) => $q->catalog())
            ->with('character.project')
            ->get()
            ->sortBy(fn (Zone $zone) => [$zone->character->project?->position, $zone->character->position, $zone->position])
            ->map(fn (Zone $zone) => ['label' => $zone->character->name.' · '.mb_strtolower($zone->name), 'hex' => strtoupper($zone->target_hex)]);

        return collect([
            ['label' => 'Pelle anime', 'hex' => '#D9A07A'],
            ['label' => 'Pelle chiara', 'hex' => '#EFCFB4'],
            ['label' => 'Oro anime', 'hex' => '#E6B83E'],
        ])->concat($zones)->concat([
            ['label' => 'Terra', 'hex' => '#6B4A30'],
            ['label' => 'Roccia', 'hex' => '#7E7B74'],
            ['label' => 'Marmo', 'hex' => '#E7E1D3'],
        ])->values()->all();
    }
}
