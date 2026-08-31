<?php

namespace App\Services\ProfileSearch;

use Exception;
use Illuminate\Support\Facades\Http;

class BraveProfileSearchService implements ProfileSearchInterface
{
    private const ENDPOINT = 'https://api.search.brave.com/res/v1/web/search';

    public function __construct(private readonly string $apiKey)
    {
    }

    /**
     * @throws Exception
     */
    public function search(string $query, int $count = 10): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('BRAVE_SEARCH_API_KEY is not configured');
        }

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-Subscription-Token' => $this->apiKey,
        ])
            ->timeout(15)
            ->get(self::ENDPOINT, [
                'q' => $query,
                // Brave counts results per page, capped at 20.
                'count' => max(1, min(20, $count)),
            ]);

        if ($response->failed()) {
            throw new Exception('Brave Search request failed: HTTP ' . $response->status() . ' ' . $response->body());
        }

        $rows = $response->json('web.results', []);

        return collect($rows)
            ->map(fn (array $row) => [
                'name' => trim((string) ($row['title'] ?? '')),
                'profile_url' => (string) ($row['url'] ?? ''),
                'snippet' => isset($row['description']) ? trim(strip_tags((string) $row['description'])) : null,
            ])
            ->filter(fn (array $row) => str_contains($row['profile_url'], 'linkedin.com/in/') && $row['name'] !== '')
            ->values()
            ->take($count)
            ->all();
    }
}
