<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectActionLastSentTest extends TestCase
{
    use RefreshDatabase;

    public function test_last_actions_includes_non_email_action_types(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);

        $sentEmail = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id, 'type' => 'email', 'status' => 'sent',
        ]);
        $loggedCall = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id, 'type' => 'call', 'status' => 'done',
        ]);
        $loggedLinkedin = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id, 'type' => 'linkedin', 'status' => 'done',
        ]);
        $stillPending = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id, 'type' => 'email', 'status' => 'pending',
        ]);
        $stillPlanned = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id, 'type' => 'email', 'status' => 'planned',
        ]);

        $response = $this->getJson('/api/prospect-actions/last-sent')->assertSuccessful()->json();

        $ids = collect($response['items'])->pluck('id')->all();
        $this->assertContains($sentEmail->id, $ids);
        $this->assertContains($loggedCall->id, $ids);
        $this->assertContains($loggedLinkedin->id, $ids);
        $this->assertNotContains($stillPending->id, $ids);
        $this->assertNotContains($stillPlanned->id, $ids);
    }
}
