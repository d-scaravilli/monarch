<?php

namespace App\Services\Resina;

use App\Models\Resina\Brush;
use App\Models\Resina\Character;
use App\Models\Resina\CharacterVersion;
use App\Models\Resina\GuideStep;
use App\Models\Resina\GuideText;
use App\Models\Resina\Paint;
use App\Models\Resina\Project;
use App\Models\Resina\Recipe;
use App\Models\Resina\RecipeStep;
use App\Models\Resina\ShopSuggestion;
use App\Models\Resina\StepTitle;
use App\Models\Resina\TechniqueGuide;
use App\Models\Resina\UserPaint;
use App\Models\Resina\Zone;
use App\Models\User;
use App\Support\ResinaReferenceSearch;
use Illuminate\Support\Collection;
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
                'choice_group' => $step->choice_group,
                'mix' => (object) $step->paints->mapWithKeys(fn (Paint $paint) => ['p'.$paint->id => $paint->pivot->drops])->all(),
            ])->all(),
        ];
    }

    /**
     * Recipes keyed by id, ready for the page (see recipe()).
     *
     * @param  Collection<int, int>|array<int, int>  $ids
     * @param  array<int, string>  $slugs
     * @return array<int, array<string, mixed>>
     */
    public function recipesById(Collection|array $ids, array $slugs = []): array
    {
        return Recipe::query()
            ->where(fn ($q) => $q->whereIn('id', collect($ids)->filter()->unique()->values())->orWhereIn('slug', $slugs))
            ->with(['category', 'steps.paints'])
            ->get()
            ->mapWithKeys(fn (Recipe $recipe) => [$recipe->id => [...$this->recipe($recipe), 'is_inline' => $recipe->is_inline]])
            ->all();
    }

    /**
     * Recipe slugs every character sheet may need besides its zones:
     * the automatic eyes and face zones and the bases.
     *
     * @param  Collection<int, Character>  $characters
     * @return array<int, string>
     */
    public function virtualRecipeSlugs(Collection $characters, ?Project $project): array
    {
        return collect(['eyes', 'face', 'base-rock', 'base-earth'])
            ->merge($project?->default_bases ?? [])
            ->merge($characters->flatMap(fn (Character $character) => $character->bases ?? []))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Everything the character sheet needs (resinaCharacterSheet in the
     * JS). Shared by catalog characters and personal figures. Expects
     * zones and versions loaded.
     *
     * @param  array{tab: string, baseUrl: string, copyUrl: ?string}  $page
     * @return array<string, mixed>
     */
    public function characterSheet(User $user, Character $character, ?Project $project, array $page): array
    {
        $characters = collect([$character]);

        return [
            ...$page,
            'paints' => $this->paints($user),
            'brushes' => $this->brushes($user),
            'suggestions' => $this->shopSuggestions($user),
            'urls' => $this->urls(),
            'project' => [
                'name' => $project?->name,
                'armorLabel' => $project?->armor_label,
                'defaultBases' => $project?->default_bases,
            ],
            'character' => $this->character($character),
            'chosenVersionId' => $this->chosenVersions($user, $characters)[$character->id],
            'recipes' => $this->recipesById($character->zones->pluck('recipe_id'), $this->virtualRecipeSlugs($characters, $project)),
            'done' => $user->resinStepProgress()
                ->where('character_id', $character->id)
                ->get(['zone_key', 'step_position'])
                ->map(fn ($progress) => $progress->zone_key.'|'.$progress->step_position)
                ->all(),
            'canUploadReferences' => $user->hasRole('admin') && ! $character->isPersonal(),
            'endpoints' => [
                'temporaryReference' => route('resina.references.temporary'),
                'version' => route('resina.characters.version', $character),
                'progress' => route('resina.progress.toggle', $character),
                'reset' => route('resina.progress.reset', $character),
            ],
        ];
    }

    /**
     * A character with its shared zones and every version's zones.
     * Expects zones and versions loaded.
     *
     * @return array<string, mixed>
     */
    public function character(Character $character): array
    {
        return [
            'id' => $character->id,
            'slug' => $character->slug,
            'name' => $character->name,
            'alias_it' => $character->alias_it,
            'subtitle' => $character->subtitle,
            'group' => $character->group?->slug,
            'search_query' => $character->search_query ?: $character->name,
            'versions_note' => $character->versions_note,
            'no_face' => $character->no_face,
            'no_eyes' => $character->no_eyes,
            'bases' => $character->bases,
            'tips' => $character->tips ?? [],
            'base_zones' => $character->zones->whereNull('character_version_id')->sortBy('position')->map(fn (Zone $zone) => $this->zone($zone))->values()->all(),
            'versions' => $character->versions->sortBy('position')->map(fn (CharacterVersion $version) => [
                ...$this->version($character, $version, $character->project),
                'zones' => $character->zones->where('character_version_id', $version->id)->sortBy('position')->map(fn (Zone $zone) => $this->zone($zone))->values()->all(),
            ])->values()->all(),
        ];
    }

    /**
     * A version with its reference photo, source, search link and where
     * to attach a new photo (the admin uses those).
     *
     * @return array<string, mixed>
     */
    public function version(Character $character, CharacterVersion $version, ?Project $project): array
    {
        $character->setRelation('project', $project);
        $stamp = $version->reference_updated_at?->timestamp;

        return [
            'id' => $version->id,
            'slug' => $version->slug,
            'label' => $version->label,
            'subtitle' => $version->subtitle,
            'note' => $version->note,
            'title' => $character->name.' · '.$version->label,
            'photo' => $version->hasReferencePhoto() ? [
                'thumb' => route('resina.references.show', [$version, 'miniatura']).'?v='.$stamp,
                'original' => route('resina.references.show', [$version, 'originale']).'?v='.$stamp,
            ] : null,
            'source' => $version->reference_source,
            'searchQuery' => ResinaReferenceSearch::query($character, $version),
            'searchUrl' => ResinaReferenceSearch::url($character, $version),
            'attachUrl' => route('resina.references.attach', $version),
            'sourceUrl' => route('resina.references.source', $version),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function zone(Zone $zone): array
    {
        return [
            'id' => $zone->id,
            'name' => $zone->name,
            'tab' => $zone->tab,
            'recipe_id' => $zone->recipe_id,
            'target_hex' => $zone->target_hex,
            'note' => $zone->note,
        ];
    }

    /**
     * The version each character is shown in for this user: the one
     * they picked, or the first.
     *
     * @param  Collection<int, Character>  $characters
     * @return array<int, ?int> character id → version id
     */
    public function chosenVersions(User $user, Collection $characters): array
    {
        $picked = $user->resinCharacterVersions()
            ->wherePivotIn('character_id', $characters->pluck('id'))
            ->get()
            ->mapWithKeys(fn (CharacterVersion $version) => [$version->pivot->character_id => $version->id]);

        return $characters->mapWithKeys(function (Character $character) use ($picked) {
            $versionIds = $character->versions->sortBy('position')->pluck('id');

            return [$character->id => $versionIds->contains($picked[$character->id] ?? null) ? $picked[$character->id] : $versionIds->first()];
        })->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function shopSuggestions(User $user): array
    {
        $ownedCodes = $user->resinPaints()->with('paint')->get()->map(fn (UserPaint $userPaint) => $userPaint->paint?->code ?? $userPaint->code)->filter()->all();

        return ShopSuggestion::orderBy('position')->get()->map(fn (ShopSuggestion $suggestion) => [
            'code' => $suggestion->code,
            'name' => $suggestion->name,
            'hex' => $suggestion->hex,
            'why' => $suggestion->why,
            'owned' => in_array($suggestion->code, $ownedCodes, true),
            'buyUrl' => route('resina.paints.buy', $suggestion),
        ])->all();
    }

    /**
     * A guide step as the step rows expect it (title as role,
     * description as usage). Expects paints loaded.
     *
     * @return array<string, mixed>
     */
    public function guideStep(GuideStep $step): array
    {
        return [
            'title' => $step->title,
            'description' => $step->description,
            'mix' => (object) $step->paints->mapWithKeys(fn (Paint $paint) => ['p'.$paint->id => $paint->pivot->drops])->all(),
        ];
    }

    /**
     * The painting-mode instructions as steps.js expects them (see
     * stepGuide() there).
     *
     * @return array{guides: array<string, array<string, mixed>>, titles: array<int, array{pattern: string, title: string}>, texts: array<string, array<int, string>>}
     */
    public function techniqueGuides(): array
    {
        return [
            'guides' => TechniqueGuide::orderBy('position')->get()->mapWithKeys(fn (TechniqueGuide $guide) => [$guide->code => [
                'name' => $guide->name,
                'preparation' => $guide->preparation,
                'steps' => $guide->steps,
                'wait_minutes' => $guide->wait_minutes,
                'result' => $guide->result,
                'mistakes' => $guide->mistakes,
            ]])->all(),
            'titles' => StepTitle::orderBy('position')->get(['pattern', 'title'])->map(fn (StepTitle $title) => $title->only(['pattern', 'title']))->all(),
            'texts' => GuideText::all()->mapWithKeys(fn (GuideText $text) => [$text->key => $text->lines])->all(),
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
            'figures' => 'resina.figures.index',
        ])->map(fn (string $name) => Route::has($name) ? route($name) : null)->all();
    }
}
