<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_a_directory(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/directories', [
            'name' => 'SaaS in Paris',
            'prompt' => 'SaaS companies based in Paris',
        ])->assertSuccessful();

        $directory = Directory::first();
        $this->assertNotNull($directory);
        $this->assertEquals($user->currentTeam->id, $directory->team_id);

        $this->getJson('/api/directories')
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'SaaS in Paris']);

        $this->patchJson("/api/directories/{$directory->id}", ['name' => 'Renamed'])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $directory->fresh()->name);

        $this->deleteJson("/api/directories/{$directory->id}")->assertSuccessful();
        $this->assertNull(Directory::find($directory->id));
    }

    public function test_user_cannot_view_or_modify_another_teams_directory(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson('/api/directories')
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $directory->id]);

        $this->getJson("/api/directories/{$directory->id}")->assertServerError();
        $this->patchJson("/api/directories/{$directory->id}", ['name' => 'Hijacked'])->assertServerError();
        $this->deleteJson("/api/directories/{$directory->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $directory->fresh()->name);
        $this->assertNotNull(Directory::find($directory->id));
    }

    public function test_user_cannot_add_a_prospect_to_another_teams_directory(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->postJson("/api/directories/{$directory->id}/prospects", [
            'name' => 'Acme Inc',
        ])->assertServerError();

        $this->assertEquals(0, $directory->prospects()->count());
    }
}
