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
     * Calculate the estimated read time in minutes for the given HTML content.
     *
     * Strips HTML tags before counting words. Always returns at least 1 minute.
     */
    public function calculate(string $content): int
    {
        $wordCount = str_word_count(strip_tags($content));

        return max(1, (int) ceil($wordCount / self::WORDS_PER_MINUTE));
    }
}
