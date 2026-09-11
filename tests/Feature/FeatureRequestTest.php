<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FeatureRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_submit_a_feature_request(): void
    {
        config(['services.developer.email' => 'dev@example.com']);

        $user = User::factory()->withPersonalTeam()->create(['name' => 'Ada Lovelace', 'email' => 'ada@example.com']);
        $this->actingAs($user);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')
                ->once()
                ->with(
                    'dev@example.com',
                    null,
                    Mockery::any(),
                    Mockery::any(),
                    'Feature request from Ada Lovelace',
                    'Please add dark mode',
                    'ada@example.com',
                    'Ada Lovelace',
                );
        });

        $this->postJson('/api/feature-requests', ['message' => 'Please add dark mode'])
            ->assertSuccessful();
    }

    public function test_a_message_is_required(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/feature-requests', ['message' => ''])
            ->assertInvalid(['message']);
    }

    public function test_fails_clearly_when_no_developer_email_is_configured(): void
    {
        config(['services.developer.email' => '']);

        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/feature-requests', ['message' => 'Please add dark mode'])
            ->assertServerError();
    }
}
