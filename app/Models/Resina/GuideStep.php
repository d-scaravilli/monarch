<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GuideStep extends Model
{
    protected $table = 'resin_guide_steps';

    protected $fillable = [
        'guide_id',
        'position',
        'title',
        'description',
    ];

    public function guide(): BelongsTo
    {
        return $this->belongsTo(Guide::class);
    }

    public function paints(): BelongsToMany
    {
        return $this->belongsToMany(Paint::class, 'resin_guide_step_paints')->withPivot('drops');
    }
}
