<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Thrown when a registered user has used all of their "Ask Quran" AI questions
 * for the current calendar month. Carries the limit so the HTTP layer can build
 * a precise, localized message without re-deriving it.
 */
final class MonthlyQuotaExceededException extends Exception
{
    private function __construct(
        public readonly int $limit,
        public readonly string $period,
    ) {
        parent::__construct("Monthly Ask Quran quota of {$limit} reached for {$period}.");
    }

    public static function forPeriod(int $limit, string $period): self
    {
        return new self($limit, $period);
    }
}
