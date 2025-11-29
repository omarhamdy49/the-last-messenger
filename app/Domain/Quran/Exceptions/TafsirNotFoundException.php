<?php

declare(strict_types=1);

namespace App\Domain\Quran\Exceptions;

use Exception;

/**
 * Exception thrown when a tafsir is not found
 */
final class TafsirNotFoundException extends Exception
{
    public static function withId(int $id): self
    {
        return new self("Tafsir with ID {$id} not found.");
    }

    public static function forVerseAndLang(int $verseId, string $lang): self
    {
        return new self("Tafsir for verse {$verseId} in language '{$lang}' not found.");
    }
}
