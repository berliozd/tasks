<?php

namespace Tests\Feature;

use App\Models\Document;
use App\Models\DocumentFlag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DocumentTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_list_update_and_delete_a_document(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $this->postJson('/api/documents', [
            'title' => 'Onboarding guide',
            'content' => "# Onboarding\n\nWelcome to the team.",
        ])->assertSuccessful();

        $document = Document::first();
        $this->assertNotNull($document);
        $this->assertEquals($user->currentTeam->id, $document->team_id);

        $this->getJson('/api/documents')
            ->assertSuccessful()
            ->assertJsonFragment(['title' => 'Onboarding guide']);

        $this->patchJson("/api/documents/{$document->id}", ['title' => 'Renamed', 'content' => $document->content])
            ->assertSuccessful();
        $this->assertEquals('Renamed', $document->fresh()->title);

        $this->deleteJson("/api/documents/{$document->id}")->assertSuccessful();
        $this->assertNull(Document::find($document->id));
    }

    public function test_creating_a_document_attaches_ai_extracted_flags_and_can_be_filtered_by_them(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);

        $response = $this->postJson('/api/documents', [
            'title' => 'Deployment runbook',
            'content' => 'How to deploy the app.',
        ])->assertSuccessful();

        $document = Document::first();
        $this->assertGreaterThan(0, $document->flags()->count());

        $flagId = $document->flags()->first()->id;

        $this->getJson('/api/documents?' . http_build_query(['flag_ids' => [$flagId]]))
            ->assertSuccessful()
            ->assertJsonFragment(['id' => $document->id]);

        $otherFlag = DocumentFlag::factory()->create(['team_id' => $user->currentTeam->id]);
        $this->getJson('/api/documents?' . http_build_query(['flag_ids' => [$otherFlag->id]]))
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $document->id]);
    }

    public function test_filtering_by_multiple_flags_requires_all_of_them(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;

        $flagA = DocumentFlag::factory()->create(['team_id' => $teamId, 'name' => 'flag-a']);
        $flagB = DocumentFlag::factory()->create(['team_id' => $teamId, 'name' => 'flag-b']);

        $both = Document::factory()->create(['team_id' => $teamId]);
        $both->flags()->attach([$flagA->id, $flagB->id]);

        $onlyA = Document::factory()->create(['team_id' => $teamId]);
        $onlyA->flags()->attach($flagA->id);

        $response = $this->getJson('/api/documents?' . http_build_query(['flag_ids' => [$flagA->id, $flagB->id]]))
            ->assertSuccessful();

        $ids = collect($response->json())->pluck('id')->all();
        $this->assertContains($both->id, $ids);
        $this->assertNotContains($onlyA->id, $ids);
    }

    public function test_updating_a_document_does_not_touch_its_flags(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $document = Document::factory()->create(['team_id' => $user->currentTeam->id]);

        $manualFlag = DocumentFlag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'manual-flag']);
        $document->flags()->attach($manualFlag->id);

        $this->patchJson("/api/documents/{$document->id}", [
            'title' => $document->title,
            'content' => 'Updated content — flags are only touched by an explicit rescan.',
        ])->assertSuccessful();

        $flagIds = $document->fresh()->flags()->pluck('document_flags.id')->all();
        $this->assertEquals([$manualFlag->id], $flagIds);
    }

    public function test_rescanning_flags_adds_new_ones_without_dropping_existing_ones(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $document = Document::factory()->create([
            'team_id' => $user->currentTeam->id,
            'content' => 'A guide about onboarding new sales reps.',
        ]);

        $manualFlag = DocumentFlag::factory()->create(['team_id' => $user->currentTeam->id, 'name' => 'manual-flag']);
        $document->flags()->attach($manualFlag->id);

        $this->postJson("/api/documents/{$document->id}/rescan-flags")->assertSuccessful();

        $flagIds = $document->fresh()->flags()->pluck('document_flags.id')->all();
        $this->assertContains($manualFlag->id, $flagIds, 'manually-attached flag should not be dropped by a rescan');
        $this->assertGreaterThan(1, count($flagIds), 'the rescan should have added at least one new flag');
    }

    public function test_deleting_a_document_removes_flags_no_longer_used_but_keeps_shared_ones(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;

        $onlyFlag = DocumentFlag::factory()->create(['team_id' => $teamId, 'name' => 'only-here']);
        $sharedFlag = DocumentFlag::factory()->create(['team_id' => $teamId, 'name' => 'shared']);

        $toDelete = Document::factory()->create(['team_id' => $teamId]);
        $toDelete->flags()->attach([$onlyFlag->id, $sharedFlag->id]);

        $kept = Document::factory()->create(['team_id' => $teamId]);
        $kept->flags()->attach($sharedFlag->id);

        $this->deleteJson("/api/documents/{$toDelete->id}")->assertSuccessful();

        $this->assertNull(DocumentFlag::find($onlyFlag->id));
        $this->assertNotNull(DocumentFlag::find($sharedFlag->id));
        $this->assertTrue($kept->flags()->where('document_flags.id', $sharedFlag->id)->exists());
    }

    public function test_user_can_delete_a_flag_removing_it_from_every_document(): void
    {
        $user = User::factory()->withPersonalTeam()->create();
        $this->actingAs($user);
        $teamId = $user->currentTeam->id;

        $flag = DocumentFlag::factory()->create(['team_id' => $teamId, 'name' => 'to-delete']);
        $doc1 = Document::factory()->create(['team_id' => $teamId]);
        $doc2 = Document::factory()->create(['team_id' => $teamId]);
        $doc1->flags()->attach($flag->id);
        $doc2->flags()->attach($flag->id);

        $this->deleteJson("/api/document-flags/{$flag->id}")->assertSuccessful();

        $this->assertNull(DocumentFlag::find($flag->id));
        $this->assertEquals(0, $doc1->flags()->count());
        $this->assertEquals(0, $doc2->flags()->count());
    }

    public function test_user_cannot_delete_another_teams_flag(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $flag = DocumentFlag::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->deleteJson("/api/document-flags/{$flag->id}")->assertServerError();
        $this->assertNotNull(DocumentFlag::find($flag->id));
    }

    public function test_user_cannot_view_or_modify_another_teams_document(): void
    {
        $owner = User::factory()->withPersonalTeam()->create();
        $document = Document::factory()->create(['team_id' => $owner->currentTeam->id]);

        $intruder = User::factory()->withPersonalTeam()->create();
        $this->actingAs($intruder);

        $this->getJson('/api/documents')
            ->assertSuccessful()
            ->assertJsonMissing(['id' => $document->id]);

        $this->getJson("/api/documents/{$document->id}")->assertServerError();
        $this->patchJson("/api/documents/{$document->id}", ['title' => 'Hijacked'])->assertServerError();
        $this->deleteJson("/api/documents/{$document->id}")->assertServerError();

        $this->assertNotEquals('Hijacked', $document->fresh()->title);
        $this->assertNotNull(Document::find($document->id));
    }
}
