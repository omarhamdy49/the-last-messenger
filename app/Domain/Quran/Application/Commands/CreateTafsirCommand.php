<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to create a tafsir (verse interpretation)
 */
final readonly class CreateTafsirCommand
{
    public function __construct(
        public int $verseId,
        public string $verseKey,
        public string $lang,
        public string $text,
        public bool $isActive = true,
    ) {}
}
