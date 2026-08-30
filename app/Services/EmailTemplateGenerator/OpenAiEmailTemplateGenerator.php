<?php

namespace App\Services\EmailTemplateGenerator;

use Exception;
use Illuminate\Support\Facades\Http;

class OpenAiEmailTemplateGenerator implements EmailTemplateGeneratorInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    /**
     * @throws Exception
     */
    public function generate(
        string $prompt,
        ?string $audienceContext = null,
        ?array $product = null,
        string $language = 'English'
    ): array {
        if (empty($this->apiKey)) {
            throw new Exception('OPENAI_API_KEY is not configured');
        }

        $userMessage = "Write an outreach email template for this goal: {$prompt}";
        if (!empty($product['name'])) {
            $userMessage .= "\n\nThe product being pitched is \"{$product['name']}\"";
            $userMessage .= !empty($product['brief']) ? ": {$product['brief']}" : '.';
            $userMessage .= " Mention the product name \"{$product['name']}\" more than once in the body"
                . " (e.g. once in the opening and once in the closing / signature).";
            if (!empty($product['website_url'])) {
                $userMessage .= " Also include the website URL {$product['website_url']} more than once in the body"
                    . " (e.g. once in the pitch and once as a call to action).";
            }
        }
        if (!empty($audienceContext)) {
            $userMessage .= "\n\nIt will be sent to prospects matching: {$audienceContext}";
        }
        $userMessage .= "\n\nWrite the subject and body in {$language}.";

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'temperature' => 0.6,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You write concise, professional cold-outreach email templates for a '
                            . 'sales-prospecting CRM tool. Write in plain text (no markdown, no HTML). Use '
                            . '"{{name}}" as a placeholder for the recipient\'s name and "{{company}}" for '
                            . 'their company name, so the same template can be reused across many prospects. '
                            . 'Keep the body short (under 150 words) and end with a clear, low-friction call '
                            . 'to action. Also produce a short internal "name" to label this template in a '
                            . 'list (a few words, not the subject line) and a real email "subject" line.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'email_template',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'name' => ['type' => 'string'],
                                'subject' => ['type' => 'string'],
                                'body' => ['type' => 'string'],
                            ],
                            'required' => ['name', 'subject', 'body'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('OpenAI request failed: ' . $response->body());
        }

        $content = $response->json('choices.0.message.content');
        $decoded = json_decode((string) $content, true);

        if (!is_array($decoded) || empty($decoded['body'])) {
            throw new Exception('OpenAI response did not contain a usable template');
        }

        return [
            'name' => trim((string) ($decoded['name'] ?? 'Untitled template')),
            'subject' => trim((string) ($decoded['subject'] ?? '')),
            'body' => trim((string) $decoded['body']),
        ];
    }
}
