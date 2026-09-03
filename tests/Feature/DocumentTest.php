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
