<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Storage;

class Module extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'image_path',
        'member_cover_image_path',
        'member_cover_style',
        'color',
        'is_active',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }

    public function imageUrl(): ?string
    {
        return $this->image_path ? Storage::disk('public')->url($this->image_path) : null;
    }

    /**
     * Cover banner image for the member-show page, shared by every member
     * card under this module — an alternative to the plain accent-color
     * gradient chosen alongside it.
     */
    public function memberCoverImageUrl(): ?string
    {
        return $this->member_cover_image_path ? Storage::disk('public')->url($this->member_cover_image_path) : null;
    }

    public function memberCoverIsTransparent(): bool
    {
        return $this->member_cover_style === 'transparent';
    }
}
