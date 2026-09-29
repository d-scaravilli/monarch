<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\ShopSuggestion;
use App\Services\Resina\ClientPayload;
use App\Support\ResinaRows;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * "Da comprare": what's missing from the kit, and the suggested paints
 * with the closest mix the user can already make. The admin edits the
 * suggested paints.
 */
class ShopController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('resina.shop.index', [
            'payload' => [
                'paints' => $this->payload->paints($user),
                'suggestions' => $this->payload->shopSuggestions($user),
            ],
        ]);
    }

    public function edit(Request $request): View
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        return view('resina.shop.edit', [
            'rows' => old('suggestions', ShopSuggestion::orderBy('position')->get(['id', 'code', 'name', 'hex', 'why'])->toArray()),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        abort_unless($request->user()->hasRole('admin'), 403);

        $data = $request->validate([
            'suggestions' => 'nullable|array|max:60',
            'suggestions.*.id' => 'nullable|integer',
            'suggestions.*.code' => ['required', 'string', 'max:30', 'distinct'],
            'suggestions.*.name' => 'required|string|max:100',
            'suggestions.*.hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'suggestions.*.why' => 'nullable|string|max:255',
        ], [
            'suggestions.*.code.required' => 'Ogni colore ha bisogno del suo codice.',
            'suggestions.*.code.distinct' => 'Due colori hanno lo stesso codice.',
            'suggestions.*.name.required' => 'Ogni colore ha bisogno di un nome.',
        ]);

        DB::transaction(function () use ($data) {
            $rows = ResinaRows::ordered($data['suggestions'] ?? null);
            $existing = ShopSuggestion::all()->keyBy('id');
            $keptIds = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id);

            // Codes are unique: drop the removed rows before reusing their codes.
            ShopSuggestion::whereNotIn('id', $keptIds)->delete();

            foreach ($rows as $index => $row) {
                $attributes = ['position' => $index + 1, 'code' => $row['code'], 'name' => $row['name'], 'hex' => strtolower($row['hex']), 'why' => $row['why'] ?? null];
                $suggestion = isset($row['id']) ? $existing->get((int) $row['id']) : null;
                $suggestion ? $suggestion->update($attributes) : ShopSuggestion::create($attributes);
            }
        });

        return redirect()->route('resina.shop.index')->with('status', 'Colori consigliati aggiornati.');
    }
}
