<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when Surah info is not found
 */
final class SurahInfoNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Surah info with ID {$id} not found.");
    }

    public static function forSurahAndLang(int $surahId, string $lang): self
    {
        return new self("Surah info for surah {$surahId} in language '{$lang}' not found.");
    }
}
