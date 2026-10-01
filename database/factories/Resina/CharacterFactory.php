<?php

namespace Database\Factories\Resina;

use App\Models\Resina\Project;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class CharacterFactory extends Factory
{
    public function definition(): array
    {
        return [
            'project_id' => Project::factory(),
            'user_id' => null,
            'slug' => fake()->unique()->slug(2),
            'name' => fake()->firstName(),
            'tips' => [],
            'source' => 'catalogo',
            'position' => 0,
        ];
    }

    /**
     * A "Le mie figure" entry: owned by a user, outside any project.
     */
    public function personal(?User $user = null): static
    {
        return $this->state(fn () => [
            'project_id' => null,
            'user_id' => $user?->id ?? User::factory(),
            'slug' => null,
            'source' => 'manuale',
        ]);
    }
}
