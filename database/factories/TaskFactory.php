<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->sentence(2),
            'description' => $this->faker->sentence(10),
            'created_at' => now(),
            'updated_at' => now(),
            'completed_at' => now(),
            'user_id' => User::factory(),

        ];
    }
}
