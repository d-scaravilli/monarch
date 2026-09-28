<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Paint;
use App\Models\Resina\ShopSuggestion;
use App\Models\User;
use App\Services\Resina\ClientPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "I miei colori": the user's own inventory. Starter paints stay; only
 * custom bottles can be added and removed (removal lives in the
 * Livewire table).
 */
class PaintController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $ownedCodes = $this->ownedCodes($user);

        return view('resina.paints.index', [
            'payload' => [
                'paints' => $this->payload->paints($user),
                'suggestions' => ShopSuggestion::orderBy('position')->get()->map(fn (ShopSuggestion $suggestion) => [
                    'id' => $suggestion->id,
                    'code' => $suggestion->code,
                    'name' => $suggestion->name,
                    'hex' => $suggestion->hex,
                    'why' => $suggestion->why,
                    'owned' => in_array($suggestion->code, $ownedCodes, true),
                    'buyUrl' => route('resina.paints.buy', $suggestion),
                ])->all(),
            ],
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'nullable|string|max:30',
            'hex' => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'type' => ['required', Rule::in(Paint::TYPES)],
            'usage' => 'nullable|string|max:255',
        ], [
            'name.required' => 'Scrivi il nome del colore.',
            'hex.regex' => 'Scegli il colore dal selettore.',
        ]);

        $request->user()->resinPaints()->create([
            ...$data,
            'paint_id' => null,
            'hex' => strtolower($data['hex']),
        ]);

        return redirect()->route('resina.paints.index')->with('status', "«{$data['name']}» aggiunto ai tuoi colori.");
    }

    /**
     * "L'ho comprato": a suggested paint joins the inventory as a custom
     * bottle, once.
     */
    public function buy(Request $request, ShopSuggestion $suggestion): RedirectResponse
    {
        $user = $request->user();

        if (! in_array($suggestion->code, $this->ownedCodes($user), true)) {
            $user->resinPaints()->create([
                'paint_id' => null,
                'name' => $suggestion->name,
                'code' => $suggestion->code,
                'hex' => $suggestion->hex,
                'type' => 'normal',
                'usage' => $suggestion->why,
            ]);
        }

        return back()->with('status', "«{$suggestion->name}» è ora tra i tuoi colori.");
    }

    /**
     * @return array<int, string>
     */
    private function ownedCodes(User $user): array
    {
        return $user->resinPaints()->with('paint')->get()
            ->map(fn ($userPaint) => $userPaint->paint?->code ?? $userPaint->code)
            ->filter()
            ->values()
            ->all();
    }
}
