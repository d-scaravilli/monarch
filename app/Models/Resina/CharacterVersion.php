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
        'reference_image_path',
        'reference_thumb_path',
        'reference_source',
        'reference_updated_at',
    ];

    /**
     * Name of the version every catalog character has when it has no
     * real ones (see the migration that creates them).
     */
    public const SINGLE_SLUG = 'unica';

    protected function casts(): array
    {
        return [
            'reference_updated_at' => 'datetime',
        ];
    }

    public function hasReferencePhoto(): bool
    {
        return $this->reference_image_path !== null;
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }

    public function zones(): HasMany
    {
        return $this->hasMany(Zone::class)->orderBy('position');
    }
}
