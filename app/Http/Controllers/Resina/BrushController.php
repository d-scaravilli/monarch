<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Brush;
use App\Services\Resina\ClientPayload;
use App\Services\Resina\StarterKit;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * "I miei pennelli". Every change saves on its own (like the
 * prototype): the page talks to these JSON endpoints and always gets
 * the whole updated list back.
 */
class BrushController extends Controller
{
    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        return view('resina.brushes.index', [
            'payload' => [
                'brushes' => $this->payload->brushes($request->user()),
                'urls' => [
                    'store' => route('resina.brushes.store'),
                    'update' => route('resina.brushes.update', '__ID__'),
                    'reset' => route('resina.brushes.reset'),
                ],
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        $user->resinBrushes()->create([
            'type' => 'tondo',
            'size' => '1',
            'metallic_only' => false,
            'position' => ($user->resinBrushes()->max('position') ?? 0) + 1,
        ]);

        return $this->list($request);
    }

    public function update(Request $request, Brush $brush): JsonResponse
    {
        abort_unless($brush->user_id === $request->user()->id, 404);

        $data = $request->validate([
            'type' => ['sometimes', Rule::in(Brush::TYPES)],
            'size' => 'sometimes|string|max:10',
            'metallic_only' => 'sometimes|boolean',
        ]);

        DB::transaction(function () use ($request, $brush, $data) {
            // Only one brush is kept for metallics at a time.
            if (! empty($data['metallic_only'])) {
                $request->user()->resinBrushes()->whereKeyNot($brush->id)->update(['metallic_only' => false]);
            }

            $brush->update(isset($data['size']) ? [...$data, 'size' => trim($data['size'])] : $data);
        });

        return $this->list($request);
    }

    public function destroy(Request $request, Brush $brush): JsonResponse
    {
        abort_unless($brush->user_id === $request->user()->id, 404);

        $brush->delete();

        return $this->list($request);
    }

    public function reset(Request $request, StarterKit $starterKit): JsonResponse
    {
        $starterKit->resetBrushes($request->user());

        return $this->list($request);
    }

    private function list(Request $request): JsonResponse
    {
        return response()->json(['brushes' => $this->payload->brushes($request->user())]);
    }
}
