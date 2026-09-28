<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * A bottle in the shared catalog (the 28 starter paints). Recipes and
 * guides reference these; each user's own inventory lives in UserPaint.
 */
class Paint extends Model
{
    use HasFactory;

    public const TYPES = ['normal', 'metallic', 'wash', 'airbrush'];

    protected $table = 'resin_paints';

    protected $fillable = [
        'code',
        'name',
        'name_en',
        'hex',
        'line',
        'type',
        'usage',
        'position',
    ];

    /**
     * The TMM airbrush bottles are metallic too (see color.js).
     */
    public function isMetallic(): bool
    {
        return in_array($this->type, ['metallic', 'airbrush'], true);
    }
}
