<?php

namespace Database\Factories;

use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NeedStageGroup>
 */
class NeedStageGroupFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->word(),
            'position' => 0,
            'team_id' => Team::factory(),
        ];
    }
}
