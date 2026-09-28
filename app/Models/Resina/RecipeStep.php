<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class RecipeStep extends Model
{
    use HasFactory;

    /**
     * Technique codes, each linked to its page in "Tecniche".
     */
    public const TECHNIQUES = ['coprente', 'sottile', 'punta', 'velatura', 'spigoli', 'wash', 'drybrush'];

    protected $table = 'resin_recipe_steps';

    protected $fillable = [
        'recipe_id',
        'position',
        'role',
        'usage',
        'optional',
        'technique',
        'coverage',
    ];

    protected function casts(): array
    {
        return [
            'optional' => 'boolean',
            'coverage' => 'integer',
        ];
    }

    public function recipe(): BelongsTo
    {
        return $this->belongsTo(Recipe::class);
    }

    public function paints(): BelongsToMany
    {
        return $this->belongsToMany(Paint::class, 'resin_recipe_step_paints')->withPivot('drops');
    }
}
