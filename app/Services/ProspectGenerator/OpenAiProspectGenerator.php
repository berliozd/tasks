<?php

namespace App\Services\ProspectGenerator;

use Exception;
use Illuminate\Support\Facades\Http;

class OpenAiProspectGenerator implements ProspectGeneratorInterface
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
    public function generate(string $prompt, int $count, array $excludeNames = []): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENAI_API_KEY is not configured');
        }

        $userMessage = "Find exactly {$count} real companies matching this brief: {$prompt}";
        if (!empty($excludeNames)) {
            $userMessage .= "\n\nDo not include any of these — they're already in the list: "
                . implode(', ', $excludeNames);
        }

        $response = Http::withToken($this->apiKey)
            ->timeout(60)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'temperature' => 0.2,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You find real, existing companies for a sales-prospecting CRM tool. '
                            . 'Every company you return must be a genuine, currently-operating business that '
                            . 'you are highly confident actually exists, identified by its real name and its '
                            . 'real, correct official website domain. Never invent, guess, or fabricate a '
                            . 'company name or a website domain. Every returned prospect also needs an '
                            . '"email": if you know a real public contact address for that company (e.g. from '
                            . 'their real "Contact us" page), use it; otherwise give a plausible generic '
                            . 'address at their real domain (e.g. contact@domain.com or info@domain.com) — a '
                            . 'guessed local part is fine, but the domain itself must be the company\'s real '
                            . 'one. If you are not confident about a real website (and therefore cannot give a '
                            . 'real-domain email either) for a company matching the brief, do not include that '
                            . 'company in the list at all — pick a different real company you are confident '
                            . 'about instead, or simply return fewer than the requested count. Every item you '
                            . 'return must have both a real website and an email; it is far better to return '
                            . 'fewer, fully-qualified prospects than to fabricate any part of a name or URL.',
                    ],
                    [
                        'role' => 'user',
                        'content' => $userMessage,
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'prospect_list',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'prospects' => [
                                    'type' => 'array',
                                    'items' => [
                                        'type' => 'object',
                                        'properties' => [
                                            'name' => ['type' => 'string'],
                                            'website' => ['type' => 'string'],
                                            'email' => ['type' => 'string'],
                                        ],
                                        'required' => ['name', 'website', 'email'],
                                        'additionalProperties' => false,
                                    ],
                                ],
                            ],
                            'required' => ['prospects'],
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
        $rows = $decoded['prospects'] ?? null;

        if (!is_array($rows)) {
            throw new Exception('OpenAI response did not contain a prospect list');
        }

        return collect($rows)
            ->take($count)
            ->map(fn (array $row) => [
                'name' => trim((string) ($row['name'] ?? '')),
                'website' => $row['website'] ?? null,
                'email' => $row['email'] ?? null,
            ])
            ->filter(fn (array $row) => $row['name'] !== '')
            ->values()
            ->all();
    }
}
