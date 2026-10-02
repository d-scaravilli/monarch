<?php

namespace App\Services\Resina;

use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\Recipe;
use App\Models\Resina\Zone;
use App\Models\User;
use App\Support\ResinaZoneTab;
use Illuminate\Support\Facades\DB;

/**
 * Creates "Le mie figure": from scratch, as a copy of a catalog
 * character («Copia nelle mie figure»), or from an analysed photo
 * (makeGuide() in the prototype).
 *
 * A zone here is ['name', 'tab', 'recipe_id', 'target_hex', 'note'];
 * the tab is always stored, so a figure doesn't depend on name rules.
 */
class FigureBuilder
{
    /**
     * The tab a photo color is assigned to → the zone name it gets
     * when the user leaves the name empty.
     */
    public const PHOTO_ZONE_LABELS = [
        'pelle' => 'Pelle', 'volto' => 'Occhi', 'capelli' => 'Capelli', 'vestiti' => 'Tuta',
        'armatura' => 'Armatura', 'dettagli' => 'Dettaglio', 'basetta' => 'Basetta',
    ];

    public function blank(User $user, string $name): Character
    {
        return $this->create($user, [
            'name' => $name,
            'source' => 'manuale',
        ], [['name' => 'Pelle', 'tab' => 'pelle', 'recipe_id' => Recipe::where('slug', 'skin-tan')->value('id'), 'target_hex' => null, 'note' => null]]);
    }

    /**
     * «Copia nelle mie figure»: the character's zones in the chosen
     * version, without the automatic face and bases.
     */
    public function copy(User $user, Character $character, ?CharacterVersion $version): Character
    {
        return $this->create($user, [
            'name' => $character->name.' (mia versione)',
            'source' => 'manuale',
            'search_query' => $character->search_query,
        ], $this->catalogZones($character, $version));
    }

    /**
     * «Crea la guida» from a photo. $colors are the photo colors the
     * user assigned to a tab: [['hex', 'tab', 'name'], …]. Each becomes
     * a free-color zone, replacing a zone with the same name (a later
     * color with the same name wins, as in the prototype).
     *
     * @param  array{character?: ?Character, version?: ?CharacterVersion, name?: ?string, series?: ?string, notes?: ?string}  $subject
     * @param  array<int, array{hex: string, tab: string, name?: ?string}>  $colors
     * @param  array{photo: string, thumb: string}  $images
     */
    public function fromPhoto(User $user, array $subject, array $colors, array $images): ?Character
    {
        $character = $subject['character'] ?? null;
        $version = $subject['version'] ?? null;

        if ($character) {
            $zones = $this->catalogZones($character, $version);
            $name = $character->name.' (dalla foto)';
            $tips = $character->tips ?? [];
            $note = $version ? trim($version->label.($version->subtitle ? ' · '.$version->subtitle : '').'. '.($version->note ?? '')) : null;
            $searchQuery = $character->search_query;
        } else {
            $zones = [];
            $name = trim($subject['name'] ?? '') ?: 'Figura dalla foto';
            $tips = [];
            $note = trim($subject['notes'] ?? '') ?: null;
            $searchQuery = trim($name.' '.($subject['series'] ?? ''));
        }

        foreach (array_values($colors) as $j => $color) {
            $zoneName = trim($color['name'] ?? '') ?: self::PHOTO_ZONE_LABELS[$color['tab']];
            $zone = ['name' => $zoneName, 'tab' => $color['tab'], 'recipe_id' => null, 'target_hex' => strtolower($color['hex']), 'note' => 'Colore preso dalla tua foto.'];

            $index = collect($zones)->search(fn (array $z) => mb_strtolower($z['name']) === mb_strtolower($zoneName));
            if ($index !== false) {
                $zones[$index] = $zone;
            } elseif (collect($zones)->contains('name', $zoneName)) {
                $zones[] = [...$zone, 'name' => $zoneName.' '.($j + 1)];
            } else {
                $zones[] = $zone;
            }
        }

        if ($zones === []) {
            return null;
        }

        return $this->create($user, [
            'name' => $name,
            'source' => 'foto',
            'tips' => array_slice($tips, 0, 12),
            'note' => $note,
            'search_query' => $searchQuery ?: null,
            'reference_image_path' => $images['photo'],
            'reference_thumb_path' => $images['thumb'],
        ], $zones);
    }

    /**
     * The zones of a catalog character in one version (charZones() of
     * the prototype, minus the automatic face and bases): the version's
     * zones replace the shared ones with the same name, the automatic
     * eyes are added when there's no eye zone. Inline steps are copied,
     * so the figure owns its own.
     *
     * @return array<int, array<string, mixed>>
     */
    public function catalogZones(Character $character, ?CharacterVersion $version): array
    {
        $character->loadMissing('zones.recipe');
        $zones = $character->zones->whereNull('character_version_id')->sortBy('position')->values()->all();

        // Version zones that don't replace a shared one come first: inside
        // each tab they're painted before the shared zones (paintingOrder()
        // in guide.js). A replacing zone keeps the shared zone's place.
        $added = [];
        if ($version) {
            foreach ($character->zones->where('character_version_id', $version->id)->sortBy('position') as $versionZone) {
                $index = null;
                foreach ($zones as $j => $zone) {
                    if ($zone->name === $versionZone->name) {
                        $index = $j;
                    }
                }
                $index === null ? $added[] = $versionZone : $zones[$index] = $versionZone;
            }
        }
        $zones = [...$added, ...$zones];

        $rows = collect($zones)
            ->filter(fn (Zone $zone) => $zone->recipe_id || $zone->target_hex)
            ->map(fn (Zone $zone) => [
                'name' => $zone->name,
                'tab' => ResinaZoneTab::for($zone->name, $zone->tab),
                'recipe_id' => $zone->recipe_id,
                'target_hex' => $zone->recipe_id ? null : $zone->target_hex,
                'note' => $zone->note,
                'inline' => (bool) $zone->recipe?->is_inline,
            ])
            ->values()
            ->all();

        $eyes = Recipe::where('slug', 'eyes')->value('id');
        $hasEyeZone = collect($rows)->contains(fn (array $row) => (bool) preg_match('/^occhi/iu', $row['name']));
        if (! $character->no_face && ! $character->no_eyes && ! $hasEyeZone && $eyes) {
            $rows[] = ['name' => 'Occhi', 'tab' => 'volto', 'recipe_id' => $eyes, 'target_hex' => null, 'note' => null, 'inline' => false];
        }

        return $rows;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<int, array<string, mixed>>  $zones
     */
    private function create(User $user, array $attributes, array $zones): Character
    {
        return DB::transaction(function () use ($user, $attributes, $zones) {
            $figure = $user->resinFigures()->create([
                ...$attributes,
                'project_id' => null,
                'slug' => null,
                'tips' => $attributes['tips'] ?? [],
            ]);

            foreach (array_values($zones) as $index => $zone) {
                $figure->zones()->create([
                    'position' => $index + 1,
                    'name' => $zone['name'],
                    'tab' => $zone['tab'],
                    'recipe_id' => ! empty($zone['inline']) ? $this->copyInlineRecipe($zone['recipe_id'], $figure)->id : $zone['recipe_id'],
                    'target_hex' => $zone['target_hex'],
                    'note' => $zone['note'],
                ]);
            }

            return $figure;
        });
    }

    private function copyInlineRecipe(int $recipeId, Character $figure): Recipe
    {
        $source = Recipe::with('steps.paints')->findOrFail($recipeId);
        $copy = Recipe::create([
            'slug' => 'inline-figura-'.$figure->id.'-'.$source->id.'-'.uniqid(),
            'recipe_category_id' => null,
            'title' => $source->title,
            'who' => null,
            'tip' => $source->tip,
            'is_inline' => true,
        ]);

        foreach ($source->steps as $step) {
            $copy->steps()->create($step->only(['position', 'role', 'usage', 'optional', 'technique', 'coverage', 'choice_group']))
                ->paints()->attach($step->paints->mapWithKeys(fn ($paint) => [$paint->id => ['drops' => $paint->pivot->drops]])->all());
        }

        return $copy;
    }
}
