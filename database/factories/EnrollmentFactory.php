<?php

namespace Database\Factories;

use App\Models\CourseEdition;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class EnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'course_edition_id' => CourseEdition::factory(),
            'enrollment_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'discount' => fake()->randomElement([0, 0, 0, 10, 20]),
            'status' => 'active',
        ];
    }
}
