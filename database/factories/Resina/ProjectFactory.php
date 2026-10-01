<?php

namespace Database\Factories\Resina;

use Illuminate\Database\Eloquent\Factories\Factory;

class ProjectFactory extends Factory
{
    public function definition(): array
    {
        return [
            'slug' => fake()->unique()->slug(2),
            'name' => 'Progetto '.fake()->word(),
            'status' => 'anteprima',
            'default_bases' => [],
            'extra_recipes' => [],
            'position' => 0,
        ];
    }
}
