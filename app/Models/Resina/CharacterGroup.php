<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterGroup extends Model
{
    protected $table = 'resin_character_groups';

    protected $fillable = [
        'project_id',
        'slug',
        'name',
        'position',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class)->orderBy('position');
    }
}
