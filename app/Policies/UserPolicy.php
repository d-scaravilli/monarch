<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Managing members (Team, scheda iscritto) is admin's affair, plus a
     * read-only, filtered slice for an instructor — everyone else falls
     * through to false (admin bypasses both methods via Gate::before).
     */
    public function viewAny(User $user): bool
    {
        return $user->hasRole('instructor');
    }

    /**
     * An instructor may open a member's scheda only if that member is
     * enrolled in one of the courses they're assigned to teach — the same
     * scope Team is filtered to (MemberController::team()).
     */
    public function view(User $user, User $member): bool
    {
        if (! $user->hasRole('instructor')) {
            return false;
        }

        $courseIds = $user->instructedCourses()->pluck('courses.id');

        return $member->enrollments()->whereIn('course_id', $courseIds)->exists();
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, User $member): bool
    {
        return false;
    }

    public function delete(User $user, User $member): bool
    {
        return false;
    }
}
