<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when a verse is not found
 */
final class VerseNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Verse with ID {$id} not found.");
    }

    public static function withKey(string $verseKey): self
    {
        return new self("Verse with key '{$verseKey}' not found.");
    }

    public static function withSurahAndVerse(int $surahNumber, int $verseNumber): self
    {
        return new self("Verse {$surahNumber}:{$verseNumber} not found.");
    }
}
