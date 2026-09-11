<?php

namespace App\Policies;

use App\Models\Enrollment;
use App\Models\User;

class EnrollmentPolicy
{
    /**
     * View a single enrollment: the member it belongs to, or an
     * instructor assigned to its course. No payment data here.
     */
    public function view(User $user, Enrollment $enrollment): bool
    {
        if ($enrollment->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $enrollment->course->instructors()->whereKey($user->id)->exists();
        }

        return false;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Enrollment $enrollment): bool
    {
        return false;
    }

    public function delete(User $user, Enrollment $enrollment): bool
    {
        return false;
    }
}
