<?php

namespace App\Services\CompanySearch;

/**
 * Placeholder implementation: returns obviously-fake results instead of
 * calling out to Brave, so tests stay hermetic (no network calls).
 */
class StubCompanySearchService implements CompanySearchInterface
{
    public function search(string $query, int $count = 10): array
    {
        $slug = str_replace(' ', '-', trim($query)) ?: 'company';

        return collect(range(1, max(1, min($count, 3))))
            ->map(fn (int $i) => [
                'name' => "Placeholder Company {$i}",
                'website' => "https://www.{$slug}-{$i}.example.com",
                'snippet' => "Placeholder result for query: \"{$query}\".",
            ])
            ->all();
    }
}
