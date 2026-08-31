<?php

namespace App\Services\ProfileSearch;

/**
 * Placeholder implementation: returns obviously-fake results instead of
 * scraping Google, so tests stay hermetic (no network calls).
 */
class StubProfileSearchService implements ProfileSearchInterface
{
    public function search(string $query, int $count = 10): array
    {
        $slug = str_replace(' ', '-', trim($query)) ?: 'prospect';

        return collect(range(1, max(1, min($count, 3))))
            ->map(fn (int $i) => [
                'name' => "Placeholder Profile {$i}",
                'profile_url' => "https://www.linkedin.com/in/{$slug}-{$i}",
                'snippet' => "Placeholder result for query: \"{$query}\".",
            ])
            ->all();
    }
}
