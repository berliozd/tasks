<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Product;
use App\Models\Prospect;
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
        $product = Product::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson('/api/directories', [
            'name' => 'SaaS in Paris',
            'prompt' => 'SaaS companies based in Paris',
            'product_id' => $product->id,
        ])->assertSuccessful();

        $directory = Directory::first();
        $this->assertNotNull($directory);
        $this->assertEquals($user->currentTeam->id, $directory->team_id);
        $this->assertEquals($product->id, $directory->product_id);

        $this->getJson('/api/directories')
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'SaaS in Paris']);

        $this->patchJson("/api/directories/{$directory->id}", ['name' => 'Renamed'])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $directory->fresh()->name);

        $this->deleteJson("/api/directories/{$directory->id}")->assertSuccessful();
        $this->assertNull(Directory::find($directory->id));
    }

    public function test_user_cannot_create_a_directory_under_another_teams_product(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $product = Product::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->postJson('/api/directories', [
            'name' => 'Hijacked directory',
            'product_id' => $product->id,
        ])->assertServerError();

        $this->assertEquals(0, $product->directories()->count());
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

    public function test_generating_prospects_skips_emails_already_used_elsewhere_in_the_same_product(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['team_id' => $user->currentTeam->id]);

        $otherDirectory = Directory::factory()->create(['team_id' => $user->currentTeam->id, 'product_id' => $product->id]);
        Prospect::factory()->create(['directory_id' => $otherDirectory->id, 'email' => 'prospect1@example.com']);

        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
            'prompt' => 'SaaS companies',
        ]);

        // The stub generator deterministically produces prospect1@example.com
        // as its first row when nothing is excluded by name yet — which
        // already belongs to another prospect under the same product.
        $this->postJson("/api/directories/{$directory->id}/generate", ['count' => 1])
            ->assertSuccessful()
            ->assertJson([]);

        $this->assertEquals(0, $directory->prospects()->count());
    }
}
