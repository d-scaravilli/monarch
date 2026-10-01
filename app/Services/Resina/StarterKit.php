<?php

namespace App\Services\Resina;

use App\Models\Resina\Paint;
use App\Models\Resina\UserProfile;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Hands every user the starting paints (the whole paint catalog) and
 * the brush kit from database/data/resina/brushes.json.
 *
 * Done once, on the first visit to the module: access can be granted
 * from several places (Gestisci, Amministrazione, new accounts) and
 * admins enter without any grant, so there's no single "activation"
 * moment to hook into. kit_assigned_at records that it happened, so a
 * user who later deletes brushes doesn't get them back unasked.
 */
class StarterKit
{
    public function ensureFor(User $user): void
    {
        if ($user->resinProfile?->kit_assigned_at !== null) {
            return;
        }

        DB::transaction(function () use ($user) {
            UserProfile::firstOrCreate(['user_id' => $user->id]);

            // Locked re-read: two first requests at once must not both hand out the kit.
            $profile = UserProfile::where('user_id', $user->id)->lockForUpdate()->first();
            if ($profile->kit_assigned_at !== null) {
                return;
            }

            foreach (Paint::orderBy('position')->pluck('id') as $paintId) {
                $user->resinPaints()->firstOrCreate(['paint_id' => $paintId]);
            }

            $this->createBrushes($user);

            $profile->update(['kit_assigned_at' => now()]);
        });

        $user->unsetRelation('resinProfile');
    }

    /**
     * "Ripristina il kit": the user's brush list goes back to the kit.
     */
    public function resetBrushes(User $user): void
    {
        DB::transaction(function () use ($user) {
            $user->resinBrushes()->delete();
            $this->createBrushes($user);
        });
    }

    private function createBrushes(User $user): void
    {
        foreach ($this->brushKit() as $brush) {
            $user->resinBrushes()->create([
                'type' => $brush['type'],
                'size' => $brush['size'],
                'metallic_only' => (bool) $brush['metallic_only'],
                'position' => $brush['position'],
            ]);
        }
    }

    /**
     * @return array<int, array{position: int, type: string, size: string, metallic_only: bool}>
     */
    private function brushKit(): array
    {
        return json_decode(file_get_contents(database_path('data/resina/brushes.json')), true, flags: JSON_THROW_ON_ERROR);
    }
}
