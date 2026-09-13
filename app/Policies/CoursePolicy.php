<?php

namespace App\Policies;

use App\Models\Course;
use App\Models\User;

class CoursePolicy
{
    /**
     * Instructors and members list only what's relevant to them; the
     * controller scopes the query, so any authenticated non-admin may ask.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * View a course's detail page (roster, lessons).
     */
    public function view(User $user, Course $course): bool
    {
        if ($user->hasRole('instructor')) {
            return $course->instructors()->whereKey($user->id)->exists();
        }

        // An "evento" is a public noticeboard: any member can look, not
        // just the ones enrolled in it.
        if ($course->isEvento()) {
            return true;
        }

        return $course->enrollments()->where('user_id', $user->id)->exists();
    }

    /**
     * Register attendance for one of the course's lessons.
     */
    public function manageAttendance(User $user, Course $course): bool
    {
        return $user->hasRole('instructor')
            && $course->instructors()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Course $course): bool
    {
        return false;
    }

    public function delete(User $user, Course $course): bool
    {
        return false;
    }
}
