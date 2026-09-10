<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Jetstream\Features;
use Tests\TestCase;

class TeamInvitationLinksTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_team_owner_can_fetch_a_copyable_signed_link_for_a_pending_invitation(): void
    {
        if (! Features::sendsTeamInvitations()) {
            $this->markTestSkipped('Team invitations not enabled.');
        }

        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $invitee = User::factory()->create(['email' => 'invitee@example.com']);
        $invitation = $user->currentTeam->teamInvitations()->create([
            'email' => 'invitee@example.com',
            'role' => 'admin',
        ]);

        $response = $this->getJson("/api/teams/{$user->currentTeam->id}/invitation-links")
            ->assertSuccessful()
            ->json();

        $this->assertCount(1, $response);
        $this->assertEquals($invitation->id, $response[0]['id']);
        $this->assertStringContainsString('/team-invitations/'.$invitation->id, $response[0]['url']);
        $this->assertStringContainsString('signature=', $response[0]['url']);

        // The link actually works for accepting the invitation.
        $this->get($response[0]['url'])->assertRedirect();
        $this->assertTrue($invitee->belongsToTeam($user->currentTeam->fresh()));
    }

    public function test_a_user_outside_the_team_cannot_fetch_its_invitation_links(): void
    {
        if (! Features::sendsTeamInvitations()) {
            $this->markTestSkipped('Team invitations not enabled.');
        }

        $owner = User::factory()->withPersonalTeam()->create();
        $owner->currentTeam->teamInvitations()->create(['email' => 'invitee@example.com', 'role' => 'admin']);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson("/api/teams/{$owner->currentTeam->id}/invitation-links")->assertForbidden();
    }
}
