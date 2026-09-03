<?php

namespace App\Repositories;

use App\Models\Document;

readonly class DocumentRepository
{
    public function create(array $data): Document
    {
        return Document::create($data);
    }

    public function find(int $id): ?Document
    {
        return Document::find($id);
    }

    public function update(Document $document, array $data): Document
    {
        $document->fill($data);
        $document->save();
        return $document;
    }

    public function destroy(Document $document): void
    {
        $document->delete();
    }
}
