<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberNote extends Model
{
    protected $fillable = [
        'user_id',
        'lesson_id',
        'created_by',
        'type',
        'description',
    ];

    public const TYPES = [
        'tecnica' => 'Nota tecnica',
        'progresso' => 'Progresso',
        'altro' => 'Altro',
        'infortunio' => 'Infortunio',
    ];

    public function typeLabel(): string
    {
        return self::TYPES[$this->type] ?? $this->type;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
