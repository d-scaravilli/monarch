<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AppSetting extends Model
{
    protected $fillable = [
        'icon_mode',
        'icon_letter',
        'icon_color',
        'icon_image_path',
        'icon_version',
    ];

    /**
     * A single settings row for the whole application (id 1), created
     * with sensible defaults the first time it's needed.
     */
    public static function current(): self
    {
        return static::query()->firstOrCreate(['id' => 1], [
            'icon_mode' => 'letter',
            'icon_letter' => Str::upper(Str::substr(config('app.name', 'Monarch'), 0, 1)),
            'icon_color' => 'gray',
            'icon_version' => (string) now()->timestamp,
        ]);
    }

    public function iconImageUrl(): ?string
    {
        return $this->icon_image_path ? Storage::disk('public')->url($this->icon_image_path) : null;
    }
}
