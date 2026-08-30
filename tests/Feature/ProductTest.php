<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_a_product(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/products', [
            'name' => 'Acme SaaS',
            'website_url' => 'https://acme.example.com',
            'brief' => 'B2B SaaS for restaurants',
            'from_label' => 'Acme Team',
            'default_reply_to_email' => 'hello@acme.example.com',
        ])->assertSuccessful();

        $product = Product::first();
        $this->assertNotNull($product);
        $this->assertEquals($user->currentTeam->id, $product->team_id);
        $this->assertEquals('https://acme.example.com', $product->website_url);
        $this->assertEquals('Acme Team', $product->from_label);
        $this->assertEquals('hello@acme.example.com', $product->default_reply_to_email);

        $this->getJson('/api/products')
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Acme SaaS']);

        $this->patchJson("/api/products/{$product->id}", ['name' => 'Renamed'])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $product->fresh()->name);

        $this->deleteJson("/api/products/{$product->id}")->assertSuccessful();
        $this->assertNull(Product::find($product->id));
    }

    public function test_user_cannot_view_or_modify_another_teams_product(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $product = Product::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson('/api/products')
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $product->id]);

        $this->getJson("/api/products/{$product->id}")->assertServerError();
        $this->patchJson("/api/products/{$product->id}", ['name' => 'Hijacked'])->assertServerError();
        $this->deleteJson("/api/products/{$product->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $product->fresh()->name);
        $this->assertNotNull(Product::find($product->id));
    }

    public function test_deleting_a_product_deletes_its_directories(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson('/api/directories', [
            'name' => 'SaaS in Paris',
            'product_id' => $product->id,
        ])->assertSuccessful();

        $this->deleteJson("/api/products/{$product->id}")->assertSuccessful();

        $this->assertEquals(0, Directory::where('product_id', $product->id)->count());
    }

    public function test_the_tree_endpoint_nests_directories_under_their_product_and_is_team_scoped(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'Acme SaaS']);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
            'name' => 'SaaS in Paris',
        ]);

        $otherTeamProduct = Product::factory()->create(['name' => 'Other team product']);

        $this->getJson('/api/products/tree')
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Acme SaaS'])
            ->assertJsonFragment(['name' => 'SaaS in Paris'])
            ->assertJsonMissing(['name' => 'Other team product']);

        $response = $this->getJson('/api/products/tree')->json();
        $this->assertEquals([$directory->id], collect($response[0]['directories'])->pluck('id')->all());
    }
}
