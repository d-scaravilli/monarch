<?php

namespace App\Models;

use Database\Factories\CourseEditionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CourseEdition extends Model
{
    /** @use HasFactory<CourseEditionFactory> */
    use HasFactory;

    protected $fillable = [
        'course_id',
        'room_id',
        'year',
        'annual_cost',
        'monthly_cost',
    ];

    protected function casts(): array
    {
        return [
            'annual_cost' => 'decimal:2',
            'monthly_cost' => 'decimal:2',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class);
    }

    public function instructors(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'course_edition_instructor');
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function lessons(): HasMany
    {
        return $this->hasMany(Lesson::class);
    }
}
