<?php

namespace Tests\Feature;

use App\Models\Directory;
use App\Models\Prospect;
use App\Models\ProspectAction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProspectActionActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_activity_over_time_counts_completed_actions_per_day_zero_filled(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);

        $today = ProspectAction::factory()->create(['prospect_id' => $prospect->id, 'status' => 'sent']);
        $today->forceFill(['updated_at' => now()])->save();

        $twoDaysAgo = ProspectAction::factory()->create(['prospect_id' => $prospect->id, 'status' => 'done']);
        $twoDaysAgo->forceFill(['updated_at' => now()->subDays(2)])->save();

        // Still pending — should not be counted as "completed".
        ProspectAction::factory()->create(['prospect_id' => $prospect->id, 'status' => 'pending']);

        $response = $this->getJson('/api/prospect-actions/activity?days=3')
            ->assertSuccessful()
            ->json();

        $this->assertCount(3, $response);
        $byDate = collect($response)->keyBy('date');

        $this->assertEquals(1, $byDate[now()->format('Y-m-d')]['count']);
        $this->assertEquals(0, $byDate[now()->subDays(1)->format('Y-m-d')]['count']);
        $this->assertEquals(1, $byDate[now()->subDays(2)->format('Y-m-d')]['count']);
    }

    public function test_activity_over_time_buckets_by_the_users_local_date_not_utc(): void
    {
        // UTC+2: 00:13 local on the target day is 22:13 UTC the day before —
        // naively grouping by the stored (UTC) date would misfile this into
        // "yesterday".
        $user = User::factory()->withPersonalTeam()->create(['timezone' => 'Europe/Paris']);
        $this->actingAs($user);
        $directory = Directory::factory()->create(['team_id' => $user->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);

        $localNow = now()->setTimezone('Europe/Paris');
        $justAfterLocalMidnight = $localNow->copy()->startOfDay()->addMinutes(13)->setTimezone('UTC');

        $action = ProspectAction::factory()->create(['prospect_id' => $prospect->id, 'status' => 'sent']);
        $action->forceFill(['updated_at' => $justAfterLocalMidnight])->save();

        $response = $this->getJson('/api/prospect-actions/activity?days=3')
            ->assertSuccessful()
            ->json();

        $byDate = collect($response)->keyBy('date');
        $localToday = $localNow->format('Y-m-d');
        $localYesterday = $localNow->copy()->subDay()->format('Y-m-d');

        $this->assertEquals(1, $byDate[$localToday]['count']);
        $this->assertEquals(0, $byDate[$localYesterday]['count']);
    }

    public function test_activity_over_time_is_scoped_to_the_current_team(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $directory = Directory::factory()->create(['team_id' => $owner->currentTeam->id]);
        $prospect = Prospect::factory()->create(['directory_id' => $directory->id]);
        ProspectAction::factory()->create(['prospect_id' => $prospect->id, 'status' => 'sent']);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $response = $this->getJson('/api/prospect-actions/activity?days=3')
            ->assertSuccessful()
            ->json();

        $total = collect($response)->sum('count');
        $this->assertEquals(0, $total);
    }
}
