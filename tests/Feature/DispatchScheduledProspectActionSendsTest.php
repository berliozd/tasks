<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Models\User;
use App\Services\MailSender\MailSenderInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DispatchScheduledProspectActionSendsTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sends_due_planned_queued_actions_and_leaves_others_alone(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);

        $due = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'status' => 'planned',
            'queued_for_send' => true,
            'scheduled_at' => Carbon::now()->subMinute(),
        ]);
        $notYetDue = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'status' => 'planned',
            'queued_for_send' => true,
            'scheduled_at' => Carbon::now()->addHour(),
        ]);
        $pendingNotQueued = ProspectAction::factory()->create([
            'prospect_id' => $prospect->id,
            'status' => 'pending',
            'queued_for_send' => false,
            'scheduled_at' => Carbon::now()->subMinute(),
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->once();
        });

        $this->artisan('prospect-actions:dispatch-scheduled-sends')->assertSuccessful();

        $this->assertEquals('sent', $due->fresh()->status);
        $this->assertEquals('planned', $notYetDue->fresh()->status);
        $this->assertEquals('pending', $pendingNotQueued->fresh()->status);
    }

    public function test_a_large_due_batch_is_paced_across_runs_instead_of_sent_in_one_burst(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id, 'email' => 'prospect@example.com']);

        $batch = ProspectAction::factory()->count(15)->create([
            'prospect_id' => $prospect->id,
            'status' => 'planned',
            'queued_for_send' => true,
            'scheduled_at' => Carbon::now()->subMinute(),
        ]);

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->times(10);
        });

        $this->artisan('prospect-actions:dispatch-scheduled-sends')->assertSuccessful();

        $sentCount = $batch->fresh()->where('status', 'sent')->count();
        $this->assertEquals(10, $sentCount);
        $this->assertEquals(5, $batch->fresh()->where('status', 'planned')->count());

        $this->mock(MailSenderInterface::class, function ($mock) {
            $mock->shouldReceive('send')->times(5);
        });

        $this->artisan('prospect-actions:dispatch-scheduled-sends')->assertSuccessful();

        $this->assertEquals(15, $batch->fresh()->where('status', 'sent')->count());
    }
}
