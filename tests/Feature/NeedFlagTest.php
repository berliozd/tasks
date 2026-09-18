<?php

namespace Tests\Feature;

use App\Models\Flag;
use App\Models\Need;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NeedFlagTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_flag_can_be_added_to_and_removed_from_a_need(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $need = Need::factory()->create(['team_id' => $user->currentTeam->id]);
        $flag = Flag::create(['name' => 'urgent', 'color' => '#ff0000', 'user_id' => $user->id]);

        $this->postJson("/api/needs/{$need->id}/flags/{$flag->id}")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'urgent']);

        $this->assertEquals([$flag->id], $need->fresh()->flags()->pluck('flags.id')->all());

        $this->deleteJson("/api/needs/{$need->id}/flags/{$flag->id}")->assertSuccessful();

        $this->assertEquals([], $need->fresh()->flags()->pluck('flags.id')->all());
    }

    public function test_flags_are_included_when_listing_and_showing_needs(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $need = Need::factory()->create(['team_id' => $user->currentTeam->id]);
        $flag = Flag::create(['name' => 'urgent', 'color' => '#ff0000', 'user_id' => $user->id]);
        $need->flags()->attach($flag->id);

        $this->getJson('/api/needs')->assertSuccessful()->assertJsonFragment(['name' => 'urgent']);
        $this->getJson("/api/needs/{$need->id}")->assertSuccessful()->assertJsonFragment(['name' => 'urgent']);
    }

    public function test_a_need_can_be_created_with_initial_flags(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $flag = Flag::create(['name' => 'urgent', 'color' => '#ff0000', 'user_id' => $user->id]);

        $this->postJson('/api/needs', ['title' => 'New need', 'flag_ids' => [$flag->id]])
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'urgent']);
    }

    public function test_cannot_flag_a_need_belonging_to_another_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $need = Need::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $flag = Flag::create(['name' => 'urgent', 'color' => '#ff0000', 'user_id' => $intruder->id]);
        $this->actingAs($intruder);

        $this->postJson("/api/needs/{$need->id}/flags/{$flag->id}")->assertServerError();
        $this->assertEquals([], $need->fresh()->flags()->pluck('flags.id')->all());
    }
}
