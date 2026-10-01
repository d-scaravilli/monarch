<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CharacterVersion extends Model
{
    protected $table = 'resin_character_versions';

    protected $fillable = [
        'character_id',
        'slug',
        'position',
        'label',
        'subtitle',
        'note',
    ];

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->orderBy('position');
    }
}
