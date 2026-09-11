<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MedicalCertificateFactory extends Factory
{
    public function definition(): array
    {
        $issueDate = fake()->dateTimeBetween('-11 months', '-1 month');

        return [
            'user_id' => User::factory(),
            'issue_date' => $issueDate,
            'expiry_date' => (clone $issueDate)->modify('+1 year'),
        ];
    }
}
