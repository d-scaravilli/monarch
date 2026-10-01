<?php

namespace Database\Factories\Resina;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Defaults to a custom bottle; pass paint_id for a catalog one.
 */
class UserPaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'paint_id' => null,
            'name' => 'Colore '.fake()->word(),
            'code' => '72.'.fake()->numerify('###'),
            'hex' => fake()->hexColor(),
            'type' => 'normal',
        ];
    }
}
