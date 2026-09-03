<?php

namespace App\Services;

use App\Models\Document;
use App\Models\DocumentFlag;
use App\Repositories\DocumentFlagRepository;
use App\Repositories\DocumentRepository;
use App\Services\DocumentFlagExtractor\DocumentFlagExtractorInterface;
use Exception;
use Illuminate\Support\Collection;

readonly class DocumentService
{
    public function __construct(
        private DocumentRepository $documentRepository,
        private DocumentFlagRepository $documentFlagRepository,
        private DocumentFlagExtractorInterface $documentFlagExtractor,
    ) {
    }

    /**
     * @return array{count: int, recent: Collection}
     */
    public function getDashboardSummary(): array
    {
        $teamId = auth()->user()->currentTeam->id;

        return [
            'count' => Document::where('team_id', $teamId)->count(),
            'recent' => Document::where('team_id', $teamId)
                ->orderByDesc('updated_at')
                ->limit(5)
                ->get(['id', 'title', 'updated_at']),
        ];
    }

    /**
     * @param array<int, int>|null $flagIds
     */
    public function getAll(?array $flagIds = null): Collection
    {
        return Document::where('team_id', auth()->user()->currentTeam->id)
            ->with('flags')
            ->when(!empty($flagIds), fn ($query) => $query->whereHas(
                'flags',
                fn ($q) => $q->whereIn('document_flags.id', $flagIds),
            ))
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * @throws Exception
     */
    public function find(int $id): Document
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);
        $document->load('flags');
        return $document;
    }

    public function create(array $data): Document
    {
        $teamId = auth()->user()->currentTeam->id;

        $document = $this->documentRepository->create([
            'team_id' => $teamId,
            'title' => $data['title'] ?? 'Untitled',
            'content' => $data['content'] ?? '',
        ]);

        // Best-effort: a flaky AI call shouldn't prevent the document itself
        // from being saved — it just comes back with no flags yet.
        try {
            $flagNames = $this->documentFlagExtractor->extract($document->title, (string) $document->content);
            $flagIds = collect($flagNames)
                ->map(fn (string $name) => $this->documentFlagRepository->findOrCreateByName($teamId, $name)->id)
                ->all();
            $document->flags()->sync($flagIds);
        } catch (Exception) {
            // Leave the document flag-less rather than failing the save.
        }

        $document->load('flags');
        return $document;
    }

    /**
     * @throws Exception
     */
    public function update(array $data, int $id): Document
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);
        $this->documentRepository->update($document, [
            'title' => $data['title'] ?? $document->title,
            'content' => array_key_exists('content', $data) ? $data['content'] : $document->content,
        ]);
        $document->load('flags');
        return $document;
    }

    /**
     * Explicit, user-triggered re-scan (not run automatically on every
     * autosave, which would mean an AI call on every debounced edit) — adds
     * any newly-relevant flags without touching ones already on the
     * document. Lets a failed AI call propagate, unlike create()'s
     * best-effort scan, since the user is waiting on this one specifically.
     *
     * @throws Exception
     */
    public function rescanFlags(int $id): Document
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);

        $flagNames = $this->documentFlagExtractor->extract($document->title, (string) $document->content);
        $newFlagIds = collect($flagNames)
            ->map(fn (string $name) => $this->documentFlagRepository->findOrCreateByName($document->team_id, $name)->id)
            ->all();
        $document->flags()->syncWithoutDetaching($newFlagIds);

        $document->load('flags');
        return $document;
    }

    /**
     * Replace a document's flags by name (creating any that don't already
     * exist for this team). Doesn't touch title/content — a separate,
     * explicit action from editing the document itself.
     *
     * @param array<int, string> $flagNames
     * @throws Exception
     */
    public function updateFlags(array $flagNames, int $id): Document
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);
        $teamId = auth()->user()->currentTeam->id;

        $flagIds = collect($flagNames)
            ->map(fn (string $name) => trim($name))
            ->filter()
            ->unique()
            ->map(fn (string $name) => $this->documentFlagRepository->findOrCreateByName($teamId, $name)->id)
            ->all();

        $document->flags()->sync($flagIds);
        $document->load('flags');
        return $document;
    }

    /**
     * Deletes the document, then garbage-collects any of its flags that are
     * no longer attached to any other document.
     *
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);

        $flagIds = $document->flags()->pluck('document_flags.id')->all();

        $this->documentRepository->destroy($document);

        if (!empty($flagIds)) {
            DocumentFlag::whereIn('id', $flagIds)->doesntHave('documents')->delete();
        }
    }

    public function getAllFlags(): Collection
    {
        return $this->documentFlagRepository->getList(auth()->user()->currentTeam->id);
    }

    /**
     * Deletes a flag outright — detaching it from every document that
     * currently has it, not just ones left unused (that's the automatic
     * cleanup destroy() already does; this is an explicit, direct delete).
     *
     * @throws Exception
     */
    public function deleteFlag(int $id): void
    {
        $flag = $this->documentFlagRepository->find($id);
        if (!$flag) {
            throw new Exception('Flag not found');
        }
        if ((int) $flag->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
        $this->documentFlagRepository->destroy($flag);
    }

    /**
     * @throws Exception
     */
    private function findDocument(int $id): Document
    {
        $document = $this->documentRepository->find($id);
        if (!$document) {
            throw new Exception('Document not found');
        }
        return $document;
    }

    /**
     * @throws Exception
     */
    private function checkPerms(Document $document): void
    {
        if ((int) $document->team_id !== (int) auth()->user()->currentTeam->id) {
            throw new Exception('Not allowed');
        }
    }
}
