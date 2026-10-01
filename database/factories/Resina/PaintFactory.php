<?php

namespace Database\Factories\Resina;

use Illuminate\Database\Eloquent\Factories\Factory;

class PaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'code' => '72.'.fake()->unique()->numerify('9##'),
            'name' => 'Colore '.fake()->unique()->word(),
            'hex' => fake()->hexColor(),
            'line' => 'Game Color',
            'type' => 'normal',
            'position' => 0,
        ];
    }

    public function metallic(): static
    {
        return $this->state(['type' => 'metallic']);
    }
}
