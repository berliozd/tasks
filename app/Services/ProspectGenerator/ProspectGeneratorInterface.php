<?php

namespace App\Services\ProspectGenerator;

interface ProspectGeneratorInterface
{
    /**
     * Generate a list of prospect rows from a free-text prompt.
     *
     * @param array<int, string> $excludeNames Names already in the directory, so the
     *     generator can avoid resurfacing the same prospects on repeat calls.
     * @return array<int, array{name: string, website: ?string, email: ?string}>
     */
    public function generate(string $prompt, int $count, array $excludeNames = []): array;
}
