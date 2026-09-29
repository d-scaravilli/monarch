<?php

namespace App\Http\Controllers\Resina;

use App\Http\Controllers\Controller;
use App\Models\Resina\Paint;
use App\Models\Resina\SavedMix;
use App\Models\Resina\UserProfile;
use App\Models\User;
use App\Services\Resina\ClientPayload;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The mixer. Its current contents live on the user's profile (so they
 * follow you across devices) and are saved as you change them; saved
 * mixes are a list of their own.
 */
class MixerController extends Controller
{
    private const MAX_PAINTS = 12;

    private const MAX_DROPS = 40;

    public function __construct(private ClientPayload $payload) {}

    public function index(Request $request): View
    {
        $user = $request->user();

        return view('resina.mixer', [
            'payload' => [
                'paints' => $this->payload->paints($user),
                'mixer' => (object) $this->sanitize($user->resinProfile()->first()?->mixer ?? [], $user),
                'saved' => $this->savedMixes($user),
                'endpoints' => [
                    'state' => route('resina.mixer.state'),
                    'store' => route('resina.mixes.store'),
                    'destroy' => route('resina.mixes.destroy', '__ID__'),
                ],
            ],
        ]);
    }

    public function saveState(Request $request): JsonResponse
    {
        $request->validate(['mix' => 'present|array']);

        $mix = $this->sanitize($request->input('mix', []), $request->user());
        UserProfile::updateOrCreate(['user_id' => $request->user()->id], ['mixer' => $mix]);

        return response()->json(['mix' => (object) $mix]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:100',
            'mix' => 'required|array|min:1',
        ]);

        $mix = $this->sanitize($data['mix'], $request->user());
        abort_if($mix === [], 422, 'La miscela è vuota.');

        $request->user()->resinSavedMixes()->create([
            'name' => trim($data['name'] ?? '') ?: 'Ricetta',
            'mix' => $mix,
        ]);

        return response()->json(['saved' => $this->savedMixes($request->user())]);
    }

    public function destroy(Request $request, SavedMix $savedMix): JsonResponse
    {
        abort_unless($savedMix->user_id === $request->user()->id, 404);

        $savedMix->delete();

        return response()->json(['saved' => $this->savedMixes($request->user())]);
    }

    /**
     * @return array<int, array{id: int, name: string, mix: object}>
     */
    private function savedMixes(User $user): array
    {
        return $user->resinSavedMixes()->get()->map(fn (SavedMix $mix) => [
            'id' => $mix->id,
            'name' => $mix->name,
            'mix' => (object) $this->sanitize($mix->mix ?? [], $user),
        ])->all();
    }

    /**
     * Keeps only real paints the user can see ("p{id}" catalog paints,
     * "u{id}" their own custom ones) with 1–40 drops.
     *
     * @param  array<string, mixed>  $mix
     * @return array<string, int>
     */
    private function sanitize(array $mix, User $user): array
    {
        $catalogIds = Paint::pluck('id')->flip();
        $customIds = $user->resinPaints()->whereNull('paint_id')->pluck('id')->flip();
        $clean = [];

        foreach ($mix as $key => $drops) {
            if (! is_string($key) || ! preg_match('/^([pu])(\d+)$/', $key, $match) || ! is_numeric($drops)) {
                continue;
            }
            $known = $match[1] === 'p' ? $catalogIds->has((int) $match[2]) : $customIds->has((int) $match[2]);
            $drops = (int) $drops;
            if ($known && $drops >= 1) {
                $clean[$key] = min($drops, self::MAX_DROPS);
            }
            if (count($clean) >= self::MAX_PAINTS) {
                break;
            }
        }

        return $clean;
    }
}
