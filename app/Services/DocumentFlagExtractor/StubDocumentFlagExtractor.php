<?php

namespace App\Services\DocumentFlagExtractor;

/**
 * Placeholder implementation: returns obviously-fake flags instead of
 * calling a real AI provider. Swap the binding in AppServiceProvider for a
 * real implementation when ready — callers only ever depend on
 * DocumentFlagExtractorInterface.
 */
class StubDocumentFlagExtractor implements DocumentFlagExtractorInterface
{
    public function extract(string $title, string $content): array
    {
        $words = array_filter(preg_split('/[^a-zA-Z0-9]+/', trim($title)) ?: []);
        $words = array_slice(array_map('mb_strtolower', $words), 0, 2);

        return array_values(array_unique([...$words, 'unreviewed']));
    }
}
