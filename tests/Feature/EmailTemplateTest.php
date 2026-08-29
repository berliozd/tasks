<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\EmailTemplate;
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
            'body' => 'Hi {{name}}, ...',
        ])->assertSuccessful();

        $template = EmailTemplate::first();
        $this->assertNotNull($template);
        $this->assertEquals($directory->id, $template->directory_id);

        $this->getJson("/api/directories/{$directory->id}/email-templates")
            ->assertSuccessful()
            ->assertJsonFragment(['name' => 'Cold intro']);

        $this->patchJson("/api/email-templates/{$template->id}", ['name' => 'Renamed', 'body' => $template->body])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $template->fresh()->name);

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
            ->assertJsonStructure(['id', 'name', 'subject', 'body']);

        $this->assertEquals(1, EmailTemplate::where('directory_id', $directory->id)->count());
    }

    public function test_user_cannot_view_or_modify_another_teams_templates(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);
        $template = EmailTemplate::factory()->create(['directory_id' => $directory->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson("/api/directories/{$directory->id}/email-templates")->assertServerError();
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
