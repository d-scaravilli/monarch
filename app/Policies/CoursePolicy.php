<?php

namespace App\Policies;

use App\Models\CourseEdition;
use App\Models\User;

class CourseEditionPolicy
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
     * View an edition's detail page (roster, lessons).
     */
    public function view(User $user, CourseEdition $courseEdition): bool
    {
        if ($user->hasRole('instructor')) {
            return $courseEdition->instructors()->whereKey($user->id)->exists();
        }

        return $courseEdition->enrollments()->where('user_id', $user->id)->exists();
    }

    /**
     * Register attendance for one of the edition's lessons.
     */
    public function manageAttendance(User $user, CourseEdition $courseEdition): bool
    {
        return $user->hasRole('instructor')
            && $courseEdition->instructors()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, CourseEdition $courseEdition): bool
    {
        return false;
    }

    public function delete(User $user, CourseEdition $courseEdition): bool
    {
        return false;
    }
}
