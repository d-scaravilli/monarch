<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Sala '.fake()->randomElement(['Blu', 'Rossa', 'Verde', 'Gialla', 'Grande']),
            'capacity' => fake()->numberBetween(10, 40),
        ];
    }
}
