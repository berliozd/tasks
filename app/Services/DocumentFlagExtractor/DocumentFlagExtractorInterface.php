<?php

namespace App\Services\DocumentFlagExtractor;

interface DocumentFlagExtractorInterface
{
    /**
     * Scan a document and suggest a handful of short topical flags for it.
     *
     * @return array<int, string>
     */
    public function extract(string $title, string $content): array;
}
