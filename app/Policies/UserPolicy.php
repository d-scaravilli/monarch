<?php

namespace App\Policies;

use App\Models\User;

class UserPolicy
{
    /**
     * Managing members (the "iscritti" list) is an admin-only affair;
     * everyone else falls through to false (admin bypasses via Gate::before).
     */
    public function viewAny(User $user): bool
    {
        return false;
    }

    public function view(User $user, User $member): bool
    {
        return false;
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
