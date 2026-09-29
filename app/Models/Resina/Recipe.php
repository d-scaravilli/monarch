<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A color recipe: ordered steps, each one a mix of paints in drops.
 * Inline recipes are the one-off step lists that belong to a single
 * character zone (e.g. Seiya's eyes); they never show in the Ricettario.
 */
class Recipe extends Model
{
    use HasFactory;

    protected $table = 'resin_recipes';

    protected $fillable = [
        'slug',
        'recipe_category_id',
        'title',
        'who',
        'tip',
        'is_inline',
    ];

    protected function casts(): array
    {
        return [
            'is_inline' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(RecipeCategory::class, 'recipe_category_id');
    }

    /**
     * Already in painting order: never re-sort them by anything else.
     */
    public function steps(): HasMany
    {
        return $this->hasMany(RecipeStep::class)->orderBy('position');
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class);
    }

    public function armorTypes(): BelongsToMany
    {
        return $this->belongsToMany(ArmorType::class, 'resin_armor_type_recipe');
    }

    /**
     * @param  Builder<Recipe>  $query
     */
    public function scopeCatalog(Builder $query): void
    {
        $query->where('is_inline', false);
    }

    /**
     * Inline recipes live and die with their zone: once no zone points
     * to one any more (zone, version, character or project deleted), it
     * goes too.
     */
    public static function deleteOrphanInline(): void
    {
        static::where('is_inline', true)->whereDoesntHave('zones')->delete();
    }
}
