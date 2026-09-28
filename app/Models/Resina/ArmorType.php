<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArmorType extends Model
{
    protected $table = 'resin_armor_types';

    protected $fillable = [
        'project_id',
        'slug',
        'position',
        'title',
        'who',
        'description',
        'guide_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function recipes(): BelongsToMany
    {
        return $this->belongsToMany(Recipe::class, 'resin_armor_type_recipe')
            ->withPivot('position')
            ->orderByPivot('position');
    }
}
