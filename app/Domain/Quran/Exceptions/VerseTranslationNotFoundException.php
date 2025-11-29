<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when a verse translation is not found
 */
final class VerseTranslationNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Verse translation with ID {$id} not found.");
    }

    public static function forVerseAndLang(int $verseId, string $lang): self
    {
        return new self("Translation for verse {$verseId} in language '{$lang}' not found.");
    }
}
