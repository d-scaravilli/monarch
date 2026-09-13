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

        // Enrolled-as-member courses, plus every "evento" regardless of
        // enrollment — events are a public noticeboard, not gated by who
        // signed up. Applies to any non-admin, instructor included: an
        // instructor who's also enrolled somewhere as a member must still
        // see that course.
        $ids = Course::query()
            ->where(fn ($q) => $q
                ->whereHas('enrollments', fn ($q2) => $q2->where('user_id', $user->id))
                ->orWhere('type', 'evento'))
            ->pluck('id');

        if ($user->hasRole('instructor')) {
            $ids = $ids->merge($user->instructedCourses()->pluck('courses.id'))->unique()->values();
        }

        return $ids;
    }
}
