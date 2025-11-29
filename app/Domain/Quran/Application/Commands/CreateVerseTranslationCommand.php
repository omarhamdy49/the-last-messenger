<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to create a verse translation
 */
final readonly class CreateVerseTranslationCommand
{
    public function __construct(
        public int $verseId,
        public string $lang,
        public string $text,
        public ?string $translatorName = null,
        public bool $isActive = true,
    ) {}
}
