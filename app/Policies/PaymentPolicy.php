<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy
{
    /**
     * Only the admin (via Gate::before) and the member the payment
     * belongs to can see it. Instructors never see payment data.
     */
    public function view(User $user, Payment $payment): bool
    {
        return $payment->enrollment->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, Payment $payment): bool
    {
        return false;
    }

    public function delete(User $user, Payment $payment): bool
    {
        return false;
    }
}
