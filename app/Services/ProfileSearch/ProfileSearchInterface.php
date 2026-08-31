<?php

namespace App\Services\ProfileSearch;

interface ProfileSearchInterface
{
    /**
     * Search the web for public profile pages matching a query.
     *
     * @return array<int, array{name: string, profile_url: string, snippet: ?string}>
     */
    public function search(string $query, int $count = 10): array;
}
