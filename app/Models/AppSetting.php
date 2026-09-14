<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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

    /**
     * A root-relative path, deliberately not Storage::disk('public')->url()
     * — that builds an absolute URL from APP_URL, and if it's out of sync
     * with the domain actually serving the request (easy to get stale),
     * the preview `<img>` on the Aspetto page points at the wrong host
     * and shows as broken, even though the file itself is perfectly fine
     * (the generated /icons/*.png files are already served this same
     * root-relative way, which is why those never had the problem).
     */
    public function iconImageUrl(): ?string
    {
        return $this->icon_image_path ? '/storage/'.$this->icon_image_path : null;
    }
}
