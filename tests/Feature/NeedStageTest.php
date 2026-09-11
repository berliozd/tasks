<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\NeedStage;
use App\Models\NeedStageGroup;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_with_no_stages_gets_the_default_pipeline_seeded_on_first_fetch(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->getJson('/api/need-stage-groups')->assertSuccessful()->json();

        $this->assertCount(3, $response);
        $this->assertEquals(10, array_sum(array_map(fn ($group) => count($group['stages']), $response)));

        // Seeding only happens once.
        $this->getJson('/api/need-stage-groups')->assertSuccessful();
        $this->assertEquals(3, NeedStageGroup::where('team_id', $user->currentTeam->id)->count());
    }

    public function test_user_can_create_update_and_delete_a_group(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/need-stage-groups', ['label' => 'My Group'])->assertSuccessful();
        $group = NeedStageGroup::first();
        $this->assertNotNull($group);
        $this->assertEquals($user->currentTeam->id, $group->team_id);

        $this->patchJson("/api/need-stage-groups/{$group->id}", ['label' => 'Renamed'])->assertSuccessful();
        $this->assertEquals('Renamed', $group->fresh()->label);

        $this->deleteJson("/api/need-stage-groups/{$group->id}")->assertSuccessful();
        $this->assertNull(NeedStageGroup::find($group->id));
    }

    public function test_cannot_delete_a_group_that_still_has_stages(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $group = NeedStageGroup::factory()->create(['team_id' => $user->currentTeam->id]);
        NeedStage::factory()->create(['team_id' => $user->currentTeam->id, 'need_stage_group_id' => $group->id]);

        $this->deleteJson("/api/need-stage-groups/{$group->id}")->assertServerError();
        $this->assertNotNull(NeedStageGroup::find($group->id));
    }

    public function test_reordering_groups_persists_position(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;
        $first = NeedStageGroup::factory()->create(['team_id' => $teamId, 'position' => 0]);
        $second = NeedStageGroup::factory()->create(['team_id' => $teamId, 'position' => 1]);

        $this->postJson('/api/need-stage-groups/reorder', ['ids' => [$second->id, $first->id]])
            ->assertSuccessful();

        $this->assertEquals(0, $second->fresh()->position);
        $this->assertEquals(1, $first->fresh()->position);
    }

    public function test_user_can_create_update_and_delete_a_stage(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $group = NeedStageGroup::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson('/api/need-stages', [
            'need_stage_group_id' => $group->id,
            'label' => 'My Stage',
            'color' => '#ff0000',
        ])->assertSuccessful();

        $stage = NeedStage::first();
        $this->assertNotNull($stage);
        $this->assertEquals($user->currentTeam->id, $stage->team_id);
        $this->assertEquals($group->id, $stage->need_stage_group_id);

        $this->patchJson("/api/need-stages/{$stage->id}", ['label' => 'Renamed', 'color' => '#00ff00'])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $stage->fresh()->label);
        $this->assertEquals('#00ff00', $stage->fresh()->color);

        $this->deleteJson("/api/need-stages/{$stage->id}")->assertSuccessful();
        $this->assertNull(NeedStage::find($stage->id));
    }

    public function test_cannot_delete_a_stage_that_still_has_needs(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $stage = NeedStage::factory()->create(['team_id' => $user->currentTeam->id]);
        Need::factory()->create(['team_id' => $user->currentTeam->id, 'need_stage_id' => $stage->id]);

        $this->deleteJson("/api/need-stages/{$stage->id}")->assertServerError();
        $this->assertNotNull(NeedStage::find($stage->id));
    }

    public function test_reordering_stages_within_a_group_persists_position(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;
        $group = NeedStageGroup::factory()->create(['team_id' => $teamId]);
        $first = NeedStage::factory()->create(['team_id' => $teamId, 'need_stage_group_id' => $group->id, 'position' => 0]);
        $second = NeedStage::factory()->create(['team_id' => $teamId, 'need_stage_group_id' => $group->id, 'position' => 1]);

        $this->postJson('/api/need-stages/reorder', ['group_id' => $group->id, 'ids' => [$second->id, $first->id]])
            ->assertSuccessful();

        $this->assertEquals(0, $second->fresh()->position);
        $this->assertEquals(1, $first->fresh()->position);
    }

    public function test_moving_a_stage_to_another_group_appends_to_the_end(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;
        $groupA = NeedStageGroup::factory()->create(['team_id' => $teamId]);
        $groupB = NeedStageGroup::factory()->create(['team_id' => $teamId]);
        NeedStage::factory()->create(['team_id' => $teamId, 'need_stage_group_id' => $groupB->id, 'position' => 0]);
        $stage = NeedStage::factory()->create(['team_id' => $teamId, 'need_stage_group_id' => $groupA->id, 'position' => 0]);

        $this->patchJson("/api/need-stages/{$stage->id}/move", ['group_id' => $groupB->id])
            ->assertSuccessful();

        $stage->refresh();
        $this->assertEquals($groupB->id, $stage->need_stage_group_id);
        $this->assertEquals(1, $stage->position);
    }

    public function test_groups_and_stages_are_scoped_to_the_current_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $group = NeedStageGroup::factory()->create(['team_id' => $owner->currentTeam->id]);
        $stage = NeedStage::factory()->create(['team_id' => $owner->currentTeam->id, 'need_stage_group_id' => $group->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->patchJson("/api/need-stage-groups/{$group->id}", ['label' => 'Hijacked'])->assertServerError();
        $this->deleteJson("/api/need-stage-groups/{$group->id}")->assertServerError();
        $this->patchJson("/api/need-stages/{$stage->id}", ['label' => 'Hijacked'])->assertServerError();
        $this->deleteJson("/api/need-stages/{$stage->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $group->fresh()->label);
        $this->assertNotEquals('Hijacked', $stage->fresh()->label);
    }
}
