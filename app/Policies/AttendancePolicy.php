<?php

namespace App\Policies;

use App\Models\Attendance;
use App\Models\User;

class AttendancePolicy
{
    public function view(User $user, Attendance $attendance): bool
    {
        if ($attendance->enrollment->user_id === $user->id) {
            return true;
        }

        if ($user->hasRole('instructor')) {
            return $attendance->lesson->courseEdition->instructors()->whereKey($user->id)->exists();
        }

        return false;
    }

    /**
     * Only an instructor assigned to the lesson's course edition may
     * record attendance (admin bypasses via Gate::before).
     */
    public function update(User $user, Attendance $attendance): bool
    {
        return $user->hasRole('instructor')
            && $attendance->lesson->courseEdition->instructors()->whereKey($user->id)->exists();
    }

    public function create(User $user): bool
    {
        return $user->hasRole('instructor');
    }

    public function delete(User $user, Attendance $attendance): bool
    {
        return false;
    }
}
