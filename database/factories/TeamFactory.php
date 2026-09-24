<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Team>
 */
class TeamFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => $this->faker->unique()->company(),
            'user_id' => User::factory(),
            'personal_team' => true,
        ];
    }

    /**
     * Factory-created teams are Pro by default in tests — Prospection,
     * Documents, and Needs are gated behind a subscription (see
     * Team::hasFeatureEnabled()), and almost every existing feature test
     * predates that gate. Tests that specifically exercise the free-tier
     * gate should call unsubscribed() to opt out.
     */
    public function configure(): static
    {
        return $this->afterCreating(function (\App\Models\Team $team) {
            if (app()->environment('testing')) {
                $team->subscriptions()->create([
                    'type' => 'default',
                    'stripe_id' => 'sub_test_' . $team->id,
                    'stripe_status' => 'active',
                    'stripe_price' => 'price_test',
                    'quantity' => 1,
                ]);
            }
        });
    }

    public function unsubscribed(): static
    {
        return $this->afterCreating(function (\App\Models\Team $team) {
            $team->subscriptions()->delete();
        });
    }
}
