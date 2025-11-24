<?php

namespace Database\Factories;

use App\Models\Novel;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Character>
 */
class CharacterFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'novel_id' => Novel::factory(),
            'name' => fake()->name(),
            'description' => fake()->paragraph(),
            'role' => fake()->randomElement(['Protagonist', 'Antagonist', 'Supporting', 'Minor']),
        ];
    }
}
