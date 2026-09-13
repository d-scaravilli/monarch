<?php

namespace App\Support;

use App\Models\Course;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * The single definition of "which courses can this user see" — reused by
 * Corsi, Lezioni and Calendario so the three pages never disagree.
 */
class VisibleCourses
{
    /**
     * @return Collection<int, int>|null null means no restriction (admin
     *                                   sees every course)
     */
    public static function idsFor(User $user): ?Collection
    {
        if ($user->hasRole('admin')) {
            return null;
        }

        if ($user->hasRole('instructor')) {
            return $user->instructedCourses()->pluck('courses.id');
        }

        // A plain member sees the courses they're enrolled in, plus every
        // "evento" regardless of enrollment — events are a public
        // noticeboard, not gated by who signed up.
        return Course::query()
            ->where(fn ($q) => $q
                ->whereHas('enrollments', fn ($q2) => $q2->where('user_id', $user->id))
                ->orWhere('type', 'evento'))
            ->pluck('id');
    }
}
