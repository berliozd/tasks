<?php

namespace App\Services\DocumentFlagExtractor;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class OpenAiDocumentFlagExtractor implements DocumentFlagExtractorInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const MAX_CONTENT_CHARS = 6000;

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    /**
     * @throws Exception
     */
    public function extract(string $title, string $content): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENAI_API_KEY is not configured');
        }

        $excerpt = Str::limit($content, self::MAX_CONTENT_CHARS, '…');

        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You read a Markdown document from a team knowledge base and suggest '
                            . '3 to 6 short topical flags (tags) describing what it\'s about, so it can later '
                            . 'be filtered by them. Each flag is 1-2 lowercase words (use a hyphen for two '
                            . 'words, e.g. "onboarding" or "api-reference"), specific to the actual content — '
                            . 'not generic words like "document" or "notes".',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Title: {$title}\n\nContent:\n{$excerpt}",
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'document_flags',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'flags' => [
                                    'type' => 'array',
                                    'items' => ['type' => 'string'],
                                ],
                            ],
                            'required' => ['flags'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('OpenAI request failed: ' . $response->body());
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);
        $flags = $decoded['flags'] ?? null;

        if (!is_array($flags)) {
            throw new Exception('OpenAI response did not contain a flag list');
        }

        return collect($flags)
            ->map(fn ($flag) => mb_strtolower(trim((string) $flag)))
            ->filter()
            ->unique()
            ->take(6)
            ->values()
            ->all();
    }
}
