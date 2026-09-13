<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class MemberProfileFactory extends Factory
{
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'fiscal_code' => strtoupper(fake()->bothify('??????##?##?###?')),
            'phone' => fake()->numerify('3## #######'),
            'notes' => null,
        ];
    }
}
