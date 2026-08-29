<?php

namespace App\Services\EmailTemplateGenerator;

/**
 * Placeholder implementation: returns an obviously-fake template instead of
 * calling a real AI provider. Swap the binding in AppServiceProvider for a
 * real implementation when ready — callers only ever depend on
 * EmailTemplateGeneratorInterface.
 */
class StubEmailTemplateGenerator implements EmailTemplateGeneratorInterface
{
    public function generate(string $prompt, ?string $audienceContext = null): array
    {
        $brief = trim($prompt) !== '' ? $prompt : 'outreach email';

        return [
            'name' => "Draft: {$brief}",
            'subject' => "[Placeholder subject] {$brief}",
            'body' => "This is a placeholder email body generated for: \"{$brief}\".\n\n"
                . "Set OPENAI_API_KEY to generate real copy instead.",
        ];
    }
}
