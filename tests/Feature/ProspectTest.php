<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_tree_endpoint_returns_only_id_and_name(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create([
            'directory_id' => $directory->id,
            'name' => 'Acme Inc',
            'website' => 'https://acme.example.com',
            'email' => 'hello@acme.example.com',
        ]);

        $response = $this->getJson("/api/directories/{$directory->id}/prospects/tree")
            ->assertSuccessful()
            ->assertJsonFragment(['id' => $prospect->id, 'name' => 'Acme Inc']);

        $row = $response->json('0');
        $this->assertEqualsCanonicalizing(['id', 'name'], array_keys($row));
    }

    public function test_the_tree_endpoint_is_scoped_to_the_current_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);
        Prospect::factory()->create(['directory_id' => $directory->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson("/api/directories/{$directory->id}/prospects/tree")->assertServerError();
    }

    public function test_find_email_fills_in_the_email_from_the_website(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create([
            'directory_id' => $directory->id,
            'website' => 'https://www.acme.example.com',
            'email' => null,
        ]);

        $this->postJson("/api/prospects/{$prospect->id}/find-email")
            ->assertSuccessful()
            ->assertJsonFragment(['email' => 'contact@acme.example.com']);

        $this->assertEquals('contact@acme.example.com', $prospect->fresh()->email);
    }

    public function test_find_email_fails_without_a_website(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create([
            'directory_id' => $directory->id,
            'website' => null,
            'email' => null,
        ]);

        $this->postJson("/api/prospects/{$prospect->id}/find-email")->assertServerError();
        $this->assertNull($prospect->fresh()->email);
    }

    public function test_find_email_is_scoped_to_the_current_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'website' => 'https://acme.example.com']);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->postJson("/api/prospects/{$prospect->id}/find-email")->assertServerError();
    }
}
