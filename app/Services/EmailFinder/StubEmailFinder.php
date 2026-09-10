<?php

namespace App\Services\EmailFinder;

/**
 * Placeholder implementation: returns a deterministic fake address instead
 * of fetching the real website, so tests stay hermetic (no network calls).
 */
class StubEmailFinder implements EmailFinderInterface
{
    public function find(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST) ?: 'example.com';
        $host = preg_replace('/^www\./', '', $host);
        return "contact@{$host}";
    }
}
