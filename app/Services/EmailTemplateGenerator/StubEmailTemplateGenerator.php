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
    public function generate(
        string $prompt,
        ?string $audienceContext = null,
        ?array $product = null,
        string $language = 'English'
    ): array {
        $brief = trim($prompt) !== '' ? $prompt : 'outreach email';

        $body = "This is a placeholder email body generated for: \"{$brief}\" (in {$language}).";

        $name = $product['name'] ?? null;
        $url = $product['website_url'] ?? null;
        // Mention the product name and URL more than once, the way a real pitch would
        // (once in the intro, once in the closing call to action).
        if ($name) {
            $body .= "\n\n{$name} helps you get this done. That's why {$name} is worth five minutes of your time.";
        }
        if (!empty($product['brief'])) {
            $body .= "\n\nProduct: {$product['brief']}";
        }
        if ($url) {
            $body .= "\n\nTake a look at {$url} — or just go straight to {$url} to get started.";
        }
        $body .= "\n\nSet OPENAI_API_KEY to generate real copy instead.";

        return [
            'name' => "Draft: {$brief}",
            'subject' => "[Placeholder subject] {$brief}",
            'body' => $body,
        ];
    }
}
