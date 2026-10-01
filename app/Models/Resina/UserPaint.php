<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A bottle the user owns: either a catalog paint (paint_id) or a custom
 * one with its own name, code and hex.
 */
class UserPaint extends Model
{
    use HasFactory;

    protected $table = 'resin_user_paints';

    protected $fillable = [
        'user_id',
        'paint_id',
        'name',
        'code',
        'hex',
        'type',
        'usage',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paint(): BelongsTo
    {
        return $this->belongsTo(Paint::class);
    }

    public function isCustom(): bool
    {
        return $this->paint_id === null;
    }
}
