<?php

namespace Database\Factories\Resina;

use App\Models\Resina\Recipe;
use Illuminate\Database\Eloquent\Factories\Factory;

class RecipeStepFactory extends Factory
{
    public function definition(): array
    {
        return [
            'recipe_id' => Recipe::factory(),
            'position' => 1,
            'role' => 'Base',
            'usage' => null,
            'optional' => false,
            'technique' => 'coprente',
            'coverage' => 100,
        ];
    }
}
