<?php

namespace App\Services\Resina;

use App\Models\Resina\Character;
use App\Models\Resina\Recipe;
use App\Models\Resina\SavedMix;
use App\Models\Resina\StepProgress;
use App\Models\Resina\UserPaint;
use App\Models\Resina\UserProfile;
use App\Models\Resina\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * "Azzera dati modulo" for 3D - Resina: wipes every user's personal
 * data — inventories, brushes, progress, chosen versions, saved mixes,
 * path progress, personal figures and their photos. The shared catalog
 * stays. Profiles go too, so everyone gets a fresh starter kit on their
 * next visit.
 */
class ModuleDataReset
{
    public function reset(): void
    {
        $figures = Character::whereNotNull('user_id')->get();
        $imagePaths = $figures->flatMap(fn (Character $figure) => [$figure->reference_image_path, $figure->reference_thumb_path])
            ->filter()
            ->values()
            ->all();

        DB::transaction(function () use ($figures) {
            // Inline recipes copied into a figure belong to it alone:
            // delete those no catalog zone still points to.
            $figureRecipeIds = Zone::whereIn('character_id', $figures->pluck('id'))->whereNotNull('recipe_id')->pluck('recipe_id');
            $catalogRecipeIds = Zone::whereNotIn('character_id', $figures->pluck('id'))->whereNotNull('recipe_id')->pluck('recipe_id');

            // Cascades to their zones, versions, progress and chosen versions.
            Character::whereKey($figures->pluck('id'))->delete();

            Recipe::where('is_inline', true)
                ->whereIn('id', $figureRecipeIds->diff($catalogRecipeIds))
                ->delete();

            StepProgress::query()->delete();
            DB::table('resin_user_character_versions')->delete();
            DB::table('resin_user_path_progress')->delete();
            SavedMix::query()->delete();
            UserPaint::query()->delete();
            DB::table('resin_brushes')->delete();
            UserProfile::query()->delete();
        });

        // Files last: if the transaction failed, the photos must still be there.
        Storage::disk('public')->delete($imagePaths);
    }
}
