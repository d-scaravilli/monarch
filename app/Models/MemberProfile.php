<?php

namespace App\Models;

use Database\Factories\MemberProfileFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MemberProfile extends Model
{
    /** @use HasFactory<MemberProfileFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'fiscal_code',
        'emergency_contact',
        'notes',
        'owns_sword',
        'shirt_given',
        'has_borrowed_equipment',
        'borrowed_equipment_notes',
    ];

    protected function casts(): array
    {
        return [
            'owns_sword' => 'boolean',
            'shirt_given' => 'boolean',
            'has_borrowed_equipment' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
