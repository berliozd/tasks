<?php

namespace Database\Factories;

use App\Models\NeedStageGroup;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\NeedStage>
 */
class NeedStageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'label' => $this->faker->word(),
            'color' => $this->faker->hexColor(),
            'position' => 0,
            'team_id' => Team::factory(),
            'need_stage_group_id' => fn (array $attributes) => NeedStageGroup::factory()
                ->create(['team_id' => $attributes['team_id']])->id,
        ];
    }
}
