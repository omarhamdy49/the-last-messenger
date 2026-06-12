<?php

declare(strict_types=1);

namespace App\Domain\Blog\Services;

/**
 * Read Time Calculator Service
 *
 * Estimates the number of minutes required to read a piece of HTML content.
 * Extracted from ArticleController to keep computation logic in the domain layer.
 */
class ReadTimeCalculatorService
{
    private const WORDS_PER_MINUTE = 200;

    /**
     * Calculate the estimated read time in minutes for the given content.
     *
     * Accepts HTML string or content blocks array.
     * Strips HTML tags before counting words. Always returns at least 1 minute.
     */
    public function calculate(string|array $content): int
    {
        $text = is_array($content) ? $this->extractTextFromBlocks($content) : strip_tags($content);
        $wordCount = str_word_count($text);

        return max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
    }

    /**
     * Extract plain text from content blocks for word counting.
     */
    private function extractTextFromBlocks(array $blocks): string
    {
        $text = '';
        foreach ($blocks as $block) {
            $type = $block['type'] ?? '';
            if ($type === 'wysiwyg') {
                $text .= ' '.strip_tags($block['content'] ?? '');
            } elseif ($type === 'verse') {
                foreach ($block['verses'] ?? [] as $verse) {
                    $text .= ' '.($verse['text'] ?? '');
                }
            } elseif ($type === 'hadith') {
                $text .= ' '.($block['text'] ?? '');
            }
        }

        return trim($text);
    }
}
