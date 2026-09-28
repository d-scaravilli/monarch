<?php

namespace App\Services\Resina;

use App\Models\Resina\Brush;
use App\Models\Resina\Paint;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeStep;
use App\Models\Resina\UserPaint;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * The data the "3D - Resina" pages hand to resources/js/resina, always
 * in the same shape. Paints carry a key used in every mix: "p{id}" for
 * a catalog paint, "u{id}" for a user's custom one.
 */
class ClientPayload
{
    /**
     * The whole catalog (recipes use it) plus the user's custom paints;
     * "owned" marks what the user actually has (finder, mixer, shop).
     *
     * @return array<int, array<string, mixed>>
     */
    public function paints(User $user): array
    {
        $userPaints = $user->resinPaints()->get();
        $ownedCatalogIds = $userPaints->whereNotNull('paint_id')->pluck('paint_id')->flip();

        $catalog = Paint::orderBy('position')->get()->map(fn (Paint $paint) => [
            'key' => 'p'.$paint->id,
            'id' => $paint->id,
            'code' => $paint->code,
            'name' => $paint->name,
            'name_en' => $paint->name_en,
            'hex' => $paint->hex,
            'type' => $paint->type,
            'line' => $paint->line,
            'usage' => $paint->usage,
            'custom' => false,
            'owned' => $ownedCatalogIds->has($paint->id),
        ]);

        $custom = $userPaints->whereNull('paint_id')->sortBy('id')->map(fn (UserPaint $userPaint) => [
            'key' => 'u'.$userPaint->id,
            'id' => $userPaint->id,
            'code' => $userPaint->code ?: '—',
            'name' => $userPaint->name,
            'name_en' => null,
            'hex' => $userPaint->hex,
            'type' => $userPaint->type,
            'line' => 'Aggiunto da te',
            'usage' => $userPaint->usage,
            'custom' => true,
            'owned' => true,
        ]);

        return $catalog->concat($custom)->values()->all();
    }

    /**
     * @return array<int, array{id: int, type: string, size: string, metallic_only: bool}>
     */
    public function brushes(User $user): array
    {
        return $user->resinBrushes()->get()->map(fn (Brush $brush) => [
            'id' => $brush->id,
            'type' => $brush->type,
            'size' => $brush->size,
            'metallic_only' => $brush->metallic_only,
        ])->all();
    }

    /**
     * Expects steps.paints and category loaded.
     *
     * @return array<string, mixed>
     */
    public function recipe(Recipe $recipe): array
    {
        return [
            'id' => $recipe->id,
            'slug' => $recipe->slug,
            'title' => $recipe->title,
            'who' => $recipe->who,
            'tip' => $recipe->tip,
            'category' => $recipe->category?->slug,
            'steps' => $recipe->steps->map(fn (RecipeStep $step) => [
                'position' => $step->position,
                'role' => $step->role,
                'usage' => $step->usage,
                'optional' => $step->optional,
                'technique' => $step->technique,
                'coverage' => $step->coverage,
                'mix' => (object) $step->paints->mapWithKeys(fn (Paint $paint) => ['p'.$paint->id => $paint->pivot->drops])->all(),
            ])->all(),
        ];
    }

    /**
     * Links the step rows point to. A page that doesn't exist yet stays
     * null, and the JS then shows plain text instead of a link.
     *
     * @return array<string, ?string>
     */
    public function urls(): array
    {
        return collect([
            'techniques' => 'resina.techniques.index',
            'mixer' => 'resina.mixer',
            'brushes' => 'resina.brushes.index',
            'recipes' => 'resina.recipes.index',
        ])->map(fn (string $name) => Route::has($name) ? route($name) : null)->all();
    }
}
