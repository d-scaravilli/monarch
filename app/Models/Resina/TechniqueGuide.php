<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;

/**
 * How a technique is done, for the painting mode: what to prepare,
 * the numbered "how", how long to wait, how it should look and the
 * mistakes to avoid. code matches the technique of the recipe steps.
 */
class TechniqueGuide extends Model
{
    protected $table = 'resin_technique_guides';

    protected $fillable = [
        'code',
        'name',
        'preparation',
        'steps',
        'wait_minutes',
        'result',
        'mistakes',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'preparation' => 'array',
            'steps' => 'array',
            'mistakes' => 'array',
        ];
    }
}
