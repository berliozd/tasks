<?php

namespace Tests\Feature;

use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeamFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_disable_and_re_enable_features(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->actingAs($owner);

        $this->getJson('/api/team-features')->assertSuccessful()->assertJson([
            'features' => Team::FEATURES,
            'disabled' => [],
        ]);

        $this->patchJson('/api/team-features', ['disabled' => ['needs', 'documents']])
            ->assertSuccessful()
            ->assertJson(['disabled' => ['needs', 'documents']]);

        $this->assertEquals(['needs', 'documents'], $owner->currentTeam->fresh()->disabled_features);

        $this->patchJson('/api/team-features', ['disabled' => []])->assertSuccessful();
        $this->assertEquals([], $owner->currentTeam->fresh()->disabled_features);
    }

    public function test_only_the_team_owner_can_update_features(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $member = User::factory()->create();
        $owner->currentTeam->users()->attach($member, ['role' => 'admin']);
        $member->switchTeam($owner->currentTeam);

        $this->actingAs($member);

        $this->patchJson('/api/team-features', ['disabled' => ['tasks']])->assertForbidden();
        $this->assertEquals([], $owner->currentTeam->fresh()->disabled_features ?? []);
    }

    public function test_unknown_feature_keys_are_rejected(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->actingAs($owner);

        $this->patchJson('/api/team-features', ['disabled' => ['not-a-feature']])
            ->assertInvalid(['disabled.0']);
    }

    public function test_a_disabled_features_pages_and_api_routes_are_blocked(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $this->actingAs($owner);
        $owner->currentTeam->forceFill(['disabled_features' => ['tasks']])->save();

        $this->get('/tasks')->assertForbidden();
        $this->getJson('/api/tasks')->assertForbidden();

        // Other features stay available.
        $this->getJson('/api/needs')->assertSuccessful();
    }
}
