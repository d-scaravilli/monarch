<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Character;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * The "fatto" checkboxes of a character sheet, saved one at a time
 * without reloading the page. A step is identified by its zone key
 * ("z{id}", "auto:eyes", "auto:face", "base:{slug}") and its position.
 */
class StepProgressController extends Controller
{
    public function toggle(Request $request, Character $character): JsonResponse
    {
        $this->authorizeCharacter($request, $character);

        $data = $request->validate([
            'zone_key' => ['required', 'string', 'max:100', 'regex:/^(z\d+|auto:(eyes|face)|base:[a-z0-9-]+)$/'],
            'step_position' => 'required|integer|min:1|max:200',
            'done' => 'required|boolean',
        ]);

        // A stored zone must belong to this character.
        if (preg_match('/^z(\d+)$/', $data['zone_key'], $match)) {
            abort_unless($character->zones()->whereKey((int) $match[1])->exists(), 422);
        }

        $key = ['user_id' => $request->user()->id, 'character_id' => $character->id, 'zone_key' => $data['zone_key'], 'step_position' => $data['step_position']];

        if ($data['done']) {
            $request->user()->resinStepProgress()->firstOrCreate($key, ['done_at' => now()]);
        } else {
            $request->user()->resinStepProgress()->where($key)->delete();
        }

        return response()->json(['done' => $data['done']]);
    }

    /**
     * "Azzera i fatto": this user's progress on this character only.
     */
    public function reset(Request $request, Character $character): JsonResponse
    {
        $this->authorizeCharacter($request, $character);

        $request->user()->resinStepProgress()->where('character_id', $character->id)->delete();

        return response()->json(['done' => []]);
    }

    /**
     * Catalog characters are open to everyone in the module; a personal
     * figure only to its owner.
     */
    private function authorizeCharacter(Request $request, Character $character): void
    {
        abort_unless($character->user_id === null || $character->user_id === $request->user()->id, 404);
    }
}
