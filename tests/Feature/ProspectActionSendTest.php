<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Product;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProspectActionSendTest extends TestCase
{
    use RefreshDatabase;

    public function test_sending_an_email_action_uses_the_platform_from_email_and_the_directorys_from_label(): void
    {
        config(['services.prospection.from_email' => 'no-reply@addeos.com']);

        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'from_label' => 'Acme Team',
        ]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'prospect@example.com',
                    Mockery::any(),
                    'no-reply@addeos.com',
                    'Acme Team',
                    'Hello',
                    'Hi there',
                    Mockery::any(),
                );
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send")->assertSuccessful();

        $this->assertEquals('Acme Team', $action->fresh()->from_label);
    }

    public function test_sending_an_email_action_can_override_the_from_email_but_keeps_the_directorys_from_label(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'from_label' => 'Acme Team',
        ]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'prospect@example.com',
                    Mockery::any(),
                    'custom@example.com',
                    'Acme Team',
                    'Hello',
                    'Hi there',
                    Mockery::any(),
                );
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send", ['from_email' => 'custom@example.com'])
            ->assertSuccessful();
    }

    public function test_the_actions_own_from_label_overrides_the_directorys(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'from_label' => 'Acme Team',
        ]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
            'from_label' => 'Personal touch',
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'prospect@example.com',
                    Mockery::any(),
                    Mockery::any(),
                    'Personal touch',
                    'Hello',
                    'Hi there',
                    Mockery::any(),
                );
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send")->assertSuccessful();

        $this->assertEquals('Personal touch', $action->fresh()->from_label);
    }

    public function test_sending_falls_back_to_the_products_from_label_and_reply_to_when_the_directory_has_none(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create([
            'team_id' => $user->currentTeam->id,
            'from_label' => 'Product Team',
            'default_reply_to_email' => 'product-reply@example.com',
        ]);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
            'from_label' => null,
            'default_reply_to_email' => null,
        ]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'prospect@example.com',
                    Mockery::any(),
                    Mockery::any(),
                    'Product Team',
                    'Hello',
                    'Hi there',
                    'product-reply@example.com',
                );
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send")->assertSuccessful();

        $this->assertEquals('product-reply@example.com', $action->fresh()->reply_to_email);
    }

    public function test_the_directorys_from_label_and_reply_to_override_the_products(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $product = Product::factory()->create([
            'team_id' => $user->currentTeam->id,
            'from_label' => 'Product Team',
            'default_reply_to_email' => 'product-reply@example.com',
        ]);
        $directory = Directory::factory()->create([
            'team_id' => $user->currentTeam->id,
            'product_id' => $product->id,
            'from_label' => 'Directory Team',
            'default_reply_to_email' => 'directory-reply@example.com',
        ]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'prospect@example.com',
                    Mockery::any(),
                    Mockery::any(),
                    'Directory Team',
                    'Hello',
                    'Hi there',
                    'directory-reply@example.com',
                );
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send")->assertSuccessful();
    }

    public function test_a_planned_action_can_be_sent(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);
        $action = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'subject' => 'Hello',
            'message' => 'Hi there',
            'status' => 'planned',
            'queued_for_send' => true,
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->postJson("/api/prospect-actions/{$action->id}/send")->assertSuccessful();

        $action->refresh();
        $this->assertEquals('sent', $action->status);
        $this->assertFalse($action->queued_for_send);
    }
}
