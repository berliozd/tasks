<?php

namespace App\Services\ProspectGenerator;

/**
 * Placeholder implementation: returns obviously-fake rows instead of calling
 * a real AI provider. Swap the binding in AppServiceProvider for a real
 * implementation (e.g. one backed by the Claude API) when ready — callers
 * only ever depend on ProspectGeneratorInterface.
 */
class StubProspectGenerator implements ProspectGeneratorInterface
{
    public function generate(string $prompt, int $count, array $excludeNames = []): array
    {
        $slug = trim($prompt) !== '' ? $prompt : 'prospect';
        // Offset the numbering so repeat calls don't just reproduce the same names.
        $offset = count($excludeNames);

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $n = $offset + $i;
            $rows[] = [
                'name' => "Prospect $n ({$slug})",
                'website' => null,
                'email' => "prospect{$n}@example.com",
            ];
        }

        return $rows;
    }
}
