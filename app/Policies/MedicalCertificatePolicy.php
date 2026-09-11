<?php

namespace App\Policies;

use App\Models\MedicalCertificate;
use App\Models\User;

class MedicalCertificatePolicy
{
    public function view(User $user, MedicalCertificate $medicalCertificate): bool
    {
        return $medicalCertificate->user_id === $user->id;
    }

    public function create(User $user): bool
    {
        return false;
    }

    public function update(User $user, MedicalCertificate $medicalCertificate): bool
    {
        return false;
    }

    public function delete(User $user, MedicalCertificate $medicalCertificate): bool
    {
        return false;
    }
}
