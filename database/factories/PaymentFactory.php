<?php

namespace Database\Factories;

use App\Models\Enrollment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => Enrollment::factory(),
            'amount' => fake()->randomElement([35, 45, 50, 60, 350, 450]),
            'method' => fake()->randomElement(['contanti', 'bonifico', 'carta']),
            'date' => fake()->dateTimeBetween('-6 months', 'now'),
            'notes' => null,
        ];
    }
}
