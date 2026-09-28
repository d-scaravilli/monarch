<?php

namespace App\Models\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-user module state: whether the starter kit (paints and brushes)
 * was already handed out, and the mixer's current contents.
 */
class UserProfile extends Model
{
    protected $table = 'resin_user_profiles';

    protected $fillable = [
        'user_id',
        'kit_assigned_at',
        'mixer',
    ];

    protected function casts(): array
    {
        return [
            'kit_assigned_at' => 'datetime',
            'mixer' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
