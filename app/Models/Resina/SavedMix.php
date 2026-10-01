<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A mixer recipe the user saved. mix maps inventory keys ("p{paint id}"
 * for catalog paints, "u{user paint id}" for custom ones) to drops.
 */
class SavedMix extends Model
{
    protected $table = 'resin_saved_mixes';

    protected $fillable = [
        'user_id',
        'name',
        'mix',
    ];

    protected function casts(): array
    {
        return [
            'mix' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
