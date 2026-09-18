<?php

namespace App\Services\DocumentFlagExtractor;

/**
 * Non-AI replacement for the old OpenAI-backed extractor: picks a handful of
 * significant words out of the title/content by frequency, weighting title
 * words higher so they surface first. No network calls, fully deterministic.
 */
class HeuristicDocumentFlagExtractor implements DocumentFlagExtractorInterface
{
    private const STOPWORDS = [
        'the', 'a', 'an', 'and', 'or', 'but', 'if', 'then', 'else', 'for', 'to', 'of', 'in', 'on', 'at',
        'by', 'with', 'is', 'are', 'was', 'were', 'be', 'been', 'being', 'this', 'that', 'these', 'those',
        'it', 'its', 'as', 'from', 'into', 'about', 'over', 'after', 'before', 'between', 'through',
        'during', 'without', 'within', 'not', 'no', 'so', 'than', 'too', 'very', 'can', 'will', 'just',
        'should', 'now', 'also', 'how', 'what', 'when', 'where', 'who', 'which', 'why', 'do', 'does',
        'did', 'done', 'have', 'has', 'had', 'you', 'your', 'we', 'our', 'they', 'their', 'he', 'she',
        'him', 'her', 'his', 'all', 'any', 'some', 'each', 'more', 'most', 'other', 'such', 'only', 'own',
        'same', 'both', 'few', 'because', 'while', 'up', 'down', 'out', 'off', 'again', 'further', 'once',
        'here', 'there',
    ];

    private const MIN_WORD_LENGTH = 4;
    private const MAX_FLAGS = 6;
    private const TITLE_WEIGHT = 3;

    public function extract(string $title, string $content): array
    {
        $counts = [];

        foreach ($this->words($title) as $word) {
            $counts[$word] = ($counts[$word] ?? 0) + self::TITLE_WEIGHT;
        }
        foreach ($this->words($content) as $word) {
            $counts[$word] = ($counts[$word] ?? 0) + 1;
        }

        arsort($counts);

        return array_slice(array_keys($counts), 0, self::MAX_FLAGS);
    }

    /**
     * @return array<int, string>
     */
    private function words(string $text): array
    {
        $tokens = preg_split('/[^\p{L}\p{N}]+/u', mb_strtolower($text)) ?: [];

        return array_values(array_filter($tokens, function ($word) {
            return mb_strlen($word) >= self::MIN_WORD_LENGTH
                && !ctype_digit($word)
                && !in_array($word, self::STOPWORDS, true);
        }));
    }
}
