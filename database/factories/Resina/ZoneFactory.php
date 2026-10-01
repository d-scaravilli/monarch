<?php

namespace Database\Factories\Resina;

use App\Models\Resina\Character;
use Illuminate\Database\Eloquent\Factories\Factory;

class ZoneFactory extends Factory
{
    public function definition(): array
    {
        return [
            'character_id' => Character::factory(),
            'character_version_id' => null,
            'position' => 1,
            'name' => 'Pelle',
            'tab' => null,
            'recipe_id' => null,
            'target_hex' => fake()->hexColor(),
        ];
    }
}
