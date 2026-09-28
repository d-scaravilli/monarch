<?php

namespace Database\Factories\Resina;

use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'title' => 'Ricetta '.fake()->word(),
            'who' => null,
            'tip' => null,
            'is_inline' => false,
        ];
    }

    public function inline(): static
    {
        return $this->state(['is_inline' => true]);
    }
}
