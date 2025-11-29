<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when a Surah is not found
 */
final class SurahNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Surah with ID {$id} not found.");
    }

    public static function withNumber(int $number): self
    {
        return new self("Surah number {$number} not found.");
    }
}
