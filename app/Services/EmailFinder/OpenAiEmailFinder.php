<?php

namespace App\Services\EmailFinder;

use Exception;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

/**
 * Fetches a handful of likely pages (homepage, contact, about) and asks AI
 * to pick out a genuine contact email — unlike a plain regex match, it can
 * tell a real "Contact us" address apart from an unrelated email-shaped
 * string incidentally present on the page (a photo credit buried in image
 * metadata, an analytics SDK's DSN, an example address, ...).
 */
class OpenAiEmailFinder implements EmailFinderInterface
{
    private const ENDPOINT = 'https://api.openai.com/v1/chat/completions';
    private const PATHS = ['', '/contact', '/contact-us', '/about', '/about-us'];
    private const MAX_CONTENT_CHARS = 6000;

    // Social/profile platforms never expose a real personal contact email in
    // public HTML, actively block scraping (LinkedIn's "authwall"), and are
    // full of embedded ad/tracking scripts that read as plausible-looking
    // emails to a model with no way to verify them — not worth attempting.
    private const BLOCKED_HOST_SUFFIXES = [
        'linkedin.com', 'facebook.com', 'instagram.com', 'twitter.com', 'x.com',
    ];

    public function __construct(
        private readonly string $apiKey,
        private readonly string $model,
    ) {
    }

    /**
     * @throws Exception
     */
    public function find(string $url): ?string
    {
        if (empty($this->apiKey)) {
            throw new Exception('OPENAI_API_KEY is not configured');
        }

        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        foreach (self::BLOCKED_HOST_SUFFIXES as $blocked) {
            if ($host === $blocked || str_ends_with($host, ".{$blocked}")) {
                return null;
            }
        }

        $pageContent = $this->collectPageContent($url);
        if (trim($pageContent) === '') {
            return null;
        }

        $email = $this->askAi($url, $pageContent);

        return ($email && filter_var($email, FILTER_VALIDATE_EMAIL)) ? mb_strtolower($email) : null;
    }

    /**
     * Fetches the homepage and a few common contact-ish paths, stripping
     * embedded app/analytics state (e.g. a Next.js __NEXT_DATA__ JSON blob)
     * before handing any of it to the model — a real mailto: link or
     * visible contact address is never inside a <script>/<style> tag.
     */
    private function collectPageContent(string $url): string
    {
        $base = rtrim($url, '/');
        $sections = [];
        $totalLength = 0;

        foreach (self::PATHS as $path) {
            if ($totalLength >= self::MAX_CONTENT_CHARS) {
                break;
            }

            $html = $this->fetchHtml($base . $path);
            if ($html === null) {
                continue;
            }

            $html = preg_replace('/<script\b[^>]*>.*?<\/script>/is', ' ', $html) ?? $html;
            $html = preg_replace('/<style\b[^>]*>.*?<\/style>/is', ' ', $html) ?? $html;
            $html = trim($html);

            if ($html === '') {
                continue;
            }

            $sections[] = $html;
            $totalLength += strlen($html);
        }

        return Str::limit(implode("\n\n", $sections), self::MAX_CONTENT_CHARS, '…');
    }

    private function fetchHtml(string $pageUrl): ?string
    {
        try {
            $response = Http::withHeaders(['User-Agent' => 'Mozilla/5.0 (compatible; ProspectionBot/1.0)'])
                ->timeout(6)
                ->connectTimeout(4)
                ->get($pageUrl);
        } catch (Throwable) {
            return null;
        }

        return $response->successful() ? $response->body() : null;
    }

    /**
     * @throws Exception
     */
    private function askAi(string $url, string $pageContent): ?string
    {
        $response = Http::withToken($this->apiKey)
            ->timeout(30)
            ->post(self::ENDPOINT, [
                'model' => $this->model,
                'temperature' => 0,
                'messages' => [
                    [
                        'role' => 'system',
                        'content' => 'You are given the raw HTML of one or more pages from a company or '
                            . 'organization\'s website. Find a genuine, general contact email address for '
                            . 'this business (e.g. from a "Contact us" link, a footer, or a masthead). '
                            . 'Never return a personal email that is clearly unrelated to contacting the '
                            . 'business itself — a photo credit buried in image metadata, an analytics or '
                            . 'error-tracking SDK identifier, an example/placeholder address, or anything '
                            . 'not actually present in the given content. Only return an email that '
                            . 'literally appears in the content. If you are not confident a real contact '
                            . 'email is present, return null.',
                    ],
                    [
                        'role' => 'user',
                        'content' => "Website: {$url}\n\nPage content:\n{$pageContent}",
                    ],
                ],
                'response_format' => [
                    'type' => 'json_schema',
                    'json_schema' => [
                        'name' => 'contact_email',
                        'strict' => true,
                        'schema' => [
                            'type' => 'object',
                            'properties' => [
                                'email' => ['type' => ['string', 'null']],
                            ],
                            'required' => ['email'],
                            'additionalProperties' => false,
                        ],
                    ],
                ],
            ]);

        if ($response->failed()) {
            throw new Exception('OpenAI request failed: ' . $response->body());
        }

        $decoded = json_decode((string) $response->json('choices.0.message.content'), true);

        return $decoded['email'] ?? null;
    }
}
