<?php

namespace App\Support;

use App\Models\Course;
use Illuminate\Support\Collection;

/**
 * The single definition of "which course year are we looking at by
 * default" — the most recent year *by value* among existing courses
 * (not the most recently *created* course's year, which can lag behind
 * if an older-year course gets entered after a newer one). Reused by
 * Team and Contabilità so year selection never diverges between pages.
 */
class CourseYear
{
    public static function default(): ?string
    {
        return Course::query()->orderByDesc('year')->value('year');
    }

    /**
     * @return Collection<int, string>
     */
    public static function options(): Collection
    {
        return Course::query()->distinct()->orderByDesc('year')->pluck('year');
    }
}
