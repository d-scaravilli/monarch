<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A brush in the user's kit. Size is free text as printed on the handle
 * ("10/0", "1", "9"); at most one brush is reserved for metallics.
 */
class Brush extends Model
{
    use HasFactory;

    public const TYPES = ['tondo', 'liner', 'spot', 'piatto', 'angolato', 'drybrush'];

    protected $table = 'resin_brushes';

    protected $fillable = [
        'user_id',
        'type',
        'size',
        'metallic_only',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'metallic_only' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
