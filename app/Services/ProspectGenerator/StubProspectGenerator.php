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
    public function generate(string $prompt, int $count): array
    {
        $slug = trim($prompt) !== '' ? $prompt : 'prospect';

        $rows = [];
        for ($i = 1; $i <= $count; $i++) {
            $rows[] = [
                'name' => "Prospect $i ({$slug})",
                'website' => null,
                'email' => null,
            ];
        }

        return $rows;
    }
}
