<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Document extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'custom_label',
        'file_path',
        'uploaded_at',
        'expiry_date',
    ];

    protected function casts(): array
    {
        return [
            'uploaded_at' => 'date',
            'expiry_date' => 'date',
        ];
    }

    public const TYPES = [
        'certificato_medico' => 'Certificato medico',
        'modulo_iscrizione' => 'Modulo iscrizione',
        'modulo_partecipazione' => 'Modulo partecipazione',
        'carta_identita' => "Carta d'identità",
        'documento_assicurazione' => 'Documento assicurazione',
        'tessera' => 'Tessera',
        'altro' => 'Altro',
    ];

    public function typeLabel(): string
    {
        if ($this->type === 'altro' && $this->custom_label) {
            return $this->custom_label;
        }

        return self::TYPES[$this->type] ?? $this->type;
    }

    public function isExpired(): bool
    {
        return $this->expiry_date !== null && $this->expiry_date->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expiry_date !== null
            && ! $this->isExpired()
            && $this->expiry_date->diffInDays(now()) <= $days;
    }

    public function fileUrl(): ?string
    {
        return $this->file_path ? Storage::disk('public')->url($this->file_path) : null;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
