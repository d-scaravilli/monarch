<?php

namespace Database\Factories;

use App\Models\CourseEdition;
use Illuminate\Database\Eloquent\Factories\Factory;

class LessonFactory extends Factory
{
    public function definition(): array
    {
        return [
            'course_edition_id' => CourseEdition::factory(),
            'date' => fake()->dateTimeBetween('-1 month', '+1 month'),
        ];
    }
}
