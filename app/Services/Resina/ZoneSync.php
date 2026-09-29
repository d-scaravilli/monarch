<?php

namespace App\Services\Resina;

use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Recipe;
use App\Models\Resina\Zone;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Saves the zone list of a character (shared zones) or of one of its
 * versions, as edited in the zone editor: rows keep their order, rows
 * without an id are new, missing rows are deleted.
 */
class ZoneSync
{
    /**
     * @param  array<int, array{id?: ?int, name: string, recipe_id?: ?int, target_hex?: ?string, tab?: ?string, note?: ?string}>  $rows
     */
    public function sync(Character $character, ?CharacterVersion $version, array $rows): void
    {
        $existing = $character->zones()
            ->where('character_version_id', $version?->id)
            ->get()
            ->keyBy('id');

        DB::transaction(function () use ($character, $version, $rows, $existing) {
            $keptIds = [];

            foreach (array_values($rows) as $index => $row) {
                $zone = isset($row['id']) ? $existing->get((int) $row['id']) : null;
                $recipeId = isset($row['recipe_id']) && $row['recipe_id'] !== '' ? (int) $row['recipe_id'] : null;

                $this->ensureRecipeAllowed($recipeId, $zone, $index);

                $attributes = [
                    'position' => $index + 1,
                    'name' => $row['name'],
                    'tab' => ($row['tab'] ?? null) ?: null,
                    'recipe_id' => $recipeId,
                    'target_hex' => isset($row['target_hex']) && $row['target_hex'] !== '' ? strtolower($row['target_hex']) : null,
                    'note' => ($row['note'] ?? null) ?: null,
                ];

                if ($zone) {
                    $zone->update($attributes);
                } else {
                    $zone = $character->zones()->create([...$attributes, 'character_version_id' => $version?->id]);
                }

                $keptIds[] = $zone->id;
            }

            Zone::whereKey($existing->keys()->diff($keptIds))->delete();
            Recipe::deleteOrphanInline();
        });
    }

    /**
     * A zone uses a catalog recipe, or keeps the inline steps it already
     * had; it can't borrow another zone's inline recipe.
     */
    private function ensureRecipeAllowed(?int $recipeId, ?Zone $zone, int $index): void
    {
        if ($recipeId === null) {
            return;
        }

        $recipe = Recipe::find($recipeId);

        if (! $recipe || ($recipe->is_inline && $zone?->recipe_id !== $recipe->id)) {
            throw ValidationException::withMessages(["zones.{$index}.recipe_id" => 'Ricetta non valida per questa zona.']);
        }
    }
}
