<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A project's step-by-step procedure ("Passo passo"), e.g. gold cloth.
 */
class Guide extends Model
{
    protected $table = 'resin_guides';

    protected $fillable = [
        'project_id',
        'slug',
        'position',
        'title',
        'intro',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function steps(): HasMany
    {
        return $this->hasMany(GuideStep::class)->orderBy('position');
    }

    public function armorTypes(): HasMany
    {
        return $this->hasMany(ArmorType::class);
    }
}
