<?php

namespace App\Models\Resina;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectLink extends Model
{
    protected $table = 'resin_project_links';

    protected $fillable = [
        'project_id',
        'position',
        'title',
        'url',
        'description',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }
}
