<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * A minimal trail of who did a sensitive/destructive admin action and
 * when — there was no audit log in the app before this, so it starts
 * small (module data reset is the first thing that records to it) and
 * can grow to cover more actions later.
 */
class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'description',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public static function record(string $action, ?string $description = null): void
    {
        self::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
        ]);
    }
}
