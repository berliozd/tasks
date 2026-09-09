<?php

namespace Tests\Feature;

use App\Models\Need;
use App\Models\NeedActivity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_a_need(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/needs', [
            'title' => 'Export orders to CSV',
            'description' => 'Business wants a CSV export on the orders screen.',
        ])->assertSuccessful();

        $need = Need::first();
        $this->assertNotNull($need);
        $this->assertEquals($user->currentTeam->id, $need->team_id);
        $this->assertEquals('discovery', $need->stage);

        $this->getJson('/api/needs')
            ->assertSuccessful()
            ->assertJsonFragment(['title' => 'Export orders to CSV']);

        $this->patchJson("/api/needs/{$need->id}", ['title' => 'Renamed'])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $need->fresh()->title);

        $this->deleteJson("/api/needs/{$need->id}")->assertSuccessful();
        $this->assertNull(Need::find($need->id));
    }

    public function test_creating_a_need_logs_a_created_activity(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/needs', ['title' => 'New need'])->assertSuccessful();
        $need = Need::first();

        $this->assertEquals(1, $need->activities()->count());
        $this->assertEquals('created', $need->activities()->first()->type);
    }

    public function test_updating_a_need_does_not_touch_its_stage(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $need = Need::factory()->create(['team_id' => $user->currentTeam->id, 'stage' => 'dev']);

        $this->patchJson("/api/needs/{$need->id}", ['title' => 'Updated title'])
            ->assertSuccessful();

        $this->assertEquals('dev', $need->fresh()->stage);
    }

    public function test_moving_a_need_to_a_new_stage_logs_an_activity_and_appends_to_the_end(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;

        Need::factory()->create(['team_id' => $teamId, 'stage' => 'dev', 'position' => 0]);
        Need::factory()->create(['team_id' => $teamId, 'stage' => 'dev', 'position' => 1]);
        $need = Need::factory()->create(['team_id' => $teamId, 'stage' => 'grooming', 'position' => 0]);

        $this->patchJson("/api/needs/{$need->id}/stage", ['stage' => 'dev'])->assertSuccessful();

        $need->refresh();
        $this->assertEquals('dev', $need->stage);
        $this->assertEquals(2, $need->position);

        $activity = $need->activities()->latest()->first();
        $this->assertEquals('stage_changed', $activity->type);
        $this->assertEquals('grooming', $activity->from_stage);
        $this->assertEquals('dev', $activity->to_stage);
    }

    public function test_moving_a_need_to_an_invalid_stage_fails(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $need = Need::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->patchJson("/api/needs/{$need->id}/stage", ['stage' => 'not-a-real-stage'])
            ->assertServerError();
        $this->assertEquals('discovery', $need->fresh()->stage);
    }

    public function test_adding_a_note_logs_an_activity(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $need = Need::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson("/api/needs/{$need->id}/notes", ['note' => 'Checked in with business, still a priority.'])
            ->assertSuccessful();

        $activity = $need->activities()->latest()->first();
        $this->assertEquals('note', $activity->type);
        $this->assertEquals('Checked in with business, still a priority.', $activity->note);
        $this->assertEquals($user->id, $activity->user_id);
    }

    public function test_reordering_within_a_stage_persists_position(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;

        $first = Need::factory()->create(['team_id' => $teamId, 'stage' => 'todo', 'position' => 0]);
        $second = Need::factory()->create(['team_id' => $teamId, 'stage' => 'todo', 'position' => 1]);

        $this->postJson('/api/needs/reorder', ['stage' => 'todo', 'ids' => [$second->id, $first->id]])
            ->assertSuccessful();

        $this->assertEquals(0, $second->fresh()->position);
        $this->assertEquals(1, $first->fresh()->position);
    }

    public function test_user_cannot_view_or_modify_another_teams_need(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $need = Need::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson("/api/needs/{$need->id}")->assertServerError();
        $this->patchJson("/api/needs/{$need->id}", ['title' => 'Hijacked'])->assertServerError();
        $this->patchJson("/api/needs/{$need->id}/stage", ['stage' => 'dev'])->assertServerError();
        $this->postJson("/api/needs/{$need->id}/notes", ['note' => 'hi'])->assertServerError();
        $this->deleteJson("/api/needs/{$need->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $need->fresh()->title);
        $this->assertNotNull(Need::find($need->id));
        $this->assertEquals(0, NeedActivity::where('need_id', $need->id)->count());
    }
}
