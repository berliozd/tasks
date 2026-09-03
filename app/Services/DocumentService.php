<?php

namespace App\Services;

use App\Models\Document;
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
     * @throws Exception
     */
    public function destroy(int $id): void
    {
        $document = $this->findDocument($id);
        $this->checkPerms($document);
        $this->documentRepository->destroy($document);
    }

    public function getAllFlags(): Collection
    {
        return $this->documentFlagRepository->getList(auth()->user()->currentTeam->id);
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
