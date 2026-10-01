<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A step marked "fatto". zone_key is "z{id}" for a stored zone, or a
 * stable key for the zones generated on the fly: "auto:eyes",
 * "auto:face" and "base:{recipe slug}".
 */
class StepProgress extends Model
{
    public $timestamps = false;

    protected $table = 'resin_step_progress';

    protected $fillable = [
        'user_id',
        'character_id',
        'zone_key',
        'step_position',
        'done_at',
    ];

    protected function casts(): array
    {
        return [
            'done_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function character(): BelongsTo
    {
        return $this->belongsTo(Character::class);
    }
}
