<?php

namespace App\Models;

use Database\Factories\DisciplineFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discipline extends Model
{
    /** @use HasFactory<DisciplineFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
    ];

    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }
}
