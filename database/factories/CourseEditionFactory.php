<?php

namespace Database\Factories;

use App\Models\Course;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseEditionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_id' => Course::factory(),
            'room_id' => Room::factory(),
            'year' => '2025/2026',
            'annual_cost' => fake()->randomElement([350, 450, 500, 600]),
            'monthly_cost' => fake()->randomElement([35, 45, 50, 60]),
        ];
    }
}
