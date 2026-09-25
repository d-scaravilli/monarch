<?php

namespace Database\Factories;

use App\Models\Discipline;
use App\Models\Room;
use Illuminate\Database\Eloquent\Factories\Factory;

class CourseFactory extends Factory
{
    public function definition(): array
    {
        return [
            'discipline_id' => Discipline::factory(),
            'room_id' => Room::factory(),
            'year' => '2025/2026',
            'annual_cost' => fake()->randomElement([350, 450, 500, 600]),
            'monthly_cost' => fake()->randomElement([35, 45, 50, 60]),
        ];
    }

    /**
     * An "evento": specific dates, and its own mandatory title shown in
     * place of the discipline name.
     */
    public function evento(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'evento',
            'title' => fake()->unique()->words(3, true),
        ]);
    }
}
