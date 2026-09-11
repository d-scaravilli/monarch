<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Yoga', 'Pilates', 'Karate', 'Nuoto', 'Spinning', 'Boxe', 'Danza Moderna']),
            'description' => fake()->sentence(12),
        ];
    }
}
