<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\EmailTemplate;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmailTemplateTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_a_template(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson("/api/directories/{$directory->id}/email-templates", [
            'name' => 'Cold intro',
            'subject' => 'Quick question',
            'language' => 'fr',
            'body' => 'Hi {{name}}, ...',
        ])->assertSuccessful();

        $template = EmailTemplate::first();
        $this->assertNotNull($template);
        $this->assertEquals($directory->id, $template->directory_id);
        $this->assertEquals('fr', $template->language);

        $this->getJson("/api/directories/{$directory->id}/email-templates")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Cold intro']);

        $this->getJson("/api/email-templates/{$template->id}")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Cold intro']);

        $this->patchJson("/api/email-templates/{$template->id}", ['name' => 'Renamed', 'body' => $template->body])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $template->fresh()->name);

        // Language is fixed at creation time and can't be changed afterwards,
        // even if the update request tries to.
        $this->patchJson("/api/email-templates/{$template->id}", ['language' => 'de', 'body' => $template->body])
            ->assertSuccessful();
        $this->assertEquals('fr', $template->fresh()->language);

        $this->deleteJson("/api/email-templates/{$template->id}")->assertSuccessful();
        $this->assertNull(EmailTemplate::find($template->id));
    }

    public function test_user_can_generate_a_template_with_the_stub_generator(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id, 'prompt' => 'SaaS founders']);

        $this->postJson("/api/directories/{$directory->id}/email-templates/generate", [
            'prompt' => 'cold intro offering a free trial',
        ])->assertSuccessful()
            ->assertJsonStructure(['id', 'name', 'subject', 'language', 'body'])
            ->assertJsonFragment(['language' => 'en']);

        $this->assertEquals(1, EmailTemplate::where('directory_id', $directory->id)->count());
    }

    public function test_user_can_generate_a_template_in_a_specific_language(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);

        $response = $this->postJson("/api/directories/{$directory->id}/email-templates/generate", [
            'prompt' => 'cold intro offering a free trial',
            'language' => 'fr',
        ])->assertSuccessful()
            ->assertJsonFragment(['language' => 'fr']);

        $this->assertStringContainsString('French', $response->json('body'));
    }

    public function test_generating_a_template_rejects_an_unsupported_language(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);

        $this->postJson("/api/directories/{$directory->id}/email-templates/generate", [
            'prompt' => 'cold intro offering a free trial',
            'language' => 'es',
        ])->assertServerError();

        $this->assertEquals(0, EmailTemplate::where('directory_id', $directory->id)->count());
    }

    public function test_generating_a_template_uses_the_products_brief(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create([
            'team_id' => $user->currentTeam->id,
            'brief' => 'AI-powered scheduling tool for barbershops',
        ]);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson("/api/directories/{$directory->id}/email-templates/generate", [
            'prompt' => 'cold intro offering a free trial',
        ])->assertSuccessful();

        $this->assertStringContainsString('AI-powered scheduling tool for barbershops', $response->json('body'));
    }

    public function test_generating_a_template_mentions_the_products_name_and_url_several_times(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create([
            'team_id' => $user->currentTeam->id,
            'name' => 'Acme Scheduler',
            'website_url' => 'https://acme.example.com',
        ]);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
        ]);

        $response = $this->postJson("/api/directories/{$directory->id}/email-templates/generate", [
            'prompt' => 'cold intro offering a free trial',
        ])->assertSuccessful();

        $body = $response->json('body');
        $this->assertEquals(2, substr_count($body, 'Acme Scheduler'));
        $this->assertEquals(2, substr_count($body, 'https://acme.example.com'));
    }

    public function test_user_cannot_view_or_modify_another_teams_templates(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);
        $template = EmailTemplate::factory()->create(['directory_id' => $directory->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson("/api/directories/{$directory->id}/email-templates")->assertServerError();
        $this->getJson("/api/email-templates/{$template->id}")->assertServerError();
        $this->patchJson("/api/email-templates/{$template->id}", ['name' => 'Hijacked', 'body' => 'x'])
            ->assertServerError();
        $this->deleteJson("/api/email-templates/{$template->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $template->fresh()->name);
        $this->assertNotNull(EmailTemplate::find($template->id));
    }

    public function test_logging_an_action_can_reference_a_template_from_the_same_directory(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);
        $template = EmailTemplate::factory()->create(['directory_id' => $directory->id]);

        $this->postJson("/api/prospects/{$prospect->id}/actions", [
            'type' => 'email',
            'message' => 'sent the intro template',
            'status' => 'sent',
            'email_template_id' => $template->id,
        ])->assertSuccessful()
            ->assertJsonFragment(['email_template_id' => $template->id]);

        $action = ProspectAction::first();
        $this->assertEquals($template->id, $action->email_template_id);

        $this->getJson("/api/prospects/{$prospect->id}/actions")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => $template->name]);
    }

    public function test_logging_an_action_rejects_a_template_from_a_different_directory(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $otherDirectory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);
        $foreignTemplate = EmailTemplate::factory()->create(['directory_id' => $otherDirectory->id]);

        $this->postJson("/api/prospects/{$prospect->id}/actions", [
            'type' => 'email',
            'message' => 'x',
            'status' => 'sent',
            'email_template_id' => $foreignTemplate->id,
        ])->assertServerError();

        $this->assertEquals(0, ProspectAction::count());
    }
}
