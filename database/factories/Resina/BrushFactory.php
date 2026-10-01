<?php

namespace Database\Factories\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class BrushFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'type' => 'tondo',
            'size' => fake()->randomElement(['10/0', '5/0', '3/0', '0', '1', '2']),
            'metallic_only' => false,
            'position' => 0,
        ];
    }
}
