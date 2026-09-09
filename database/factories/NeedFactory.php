<?php

namespace Database\Factories;

use App\Models\Need;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Need>
 */
class NeedFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => $this->faker->sentence(4),
            'stage' => Need::STAGES[0],
            'position' => 0,
            'team_id' => Team::factory(),
        ];
    }
}
