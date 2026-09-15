<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Goal extends Model
{
    protected $fillable = [
        'enrollment_id',
        'title',
        'starting_point',
        'target',
        'status',
        'completed_at',
        'completion_description',
        'completion_references',
        'completion_note',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'completed_at' => 'date',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Notes written along the way toward (or documenting) this goal —
     * "il filo che ci ha portato" shown when the goal is expanded.
     */
    public function notes(): HasMany
    {
        return $this->hasMany(MemberNote::class)->latest();
    }

    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }
}
