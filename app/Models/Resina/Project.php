<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $table = 'resin_projects';

    protected $fillable = [
        'slug',
        'name',
        'subtitle',
        'theme',
        'status',
        'intro',
        'armor_label',
        'default_bases',
        'extra_recipes',
        'cover_image_path',
        'position',
    ];

    /**
     * default_bases and extra_recipes hold recipe slugs, which never
     * change once a recipe exists.
     */
    protected function casts(): array
    {
        return [
            'default_bases' => 'array',
            'extra_recipes' => 'array',
        ];
    }

    public function groups(): HasMany
    {
        return $this->hasMany(CharacterGroup::class)->orderBy('position');
    }

    public function characters(): HasMany
    {
        return $this->hasMany(Character::class)->orderBy('position');
    }

    public function links(): HasMany
    {
        return $this->hasMany(ProjectLink::class)->orderBy('position');
    }

    public function armorTypes(): HasMany
    {
        return $this->hasMany(ArmorType::class)->orderBy('position');
    }

    public function guides(): HasMany
    {
        return $this->hasMany(Guide::class)->orderBy('position');
    }
}
