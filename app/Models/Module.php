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
        'enrollments_bg_image_path',
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
     * Background image for the "iscritti" tables (course-enrollments-table),
     * shared by every course under this module — an alternative to the
     * plain accent-color background chosen alongside it.
     */
    public function enrollmentsBackgroundUrl(): ?string
    {
        return $this->enrollments_bg_image_path ? Storage::disk('public')->url($this->enrollments_bg_image_path) : null;
    }
}
