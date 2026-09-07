<?php

namespace App\Services\CompanySearch;

interface CompanySearchInterface
{
    /**
     * Search the general web for companies/websites matching a query — a
     * plain keyword search, unlike ProspectGeneratorInterface which asks an
     * AI to invent a curated list.
     *
     * @return array<int, array{name: string, website: string, snippet: ?string}>
     */
    public function search(string $query, int $count = 10): array;
}
