<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Commands;

/**
 * Command to create a verse (ayah)
 *
 * Used during Quran data import
 */
final readonly class CreateVerseCommand
{
    public function __construct(
        public int $surahId,
        public int $verseNumber,
        public string $verseKey,
        public int $juzNumber,
        public int $hizbNumber,
        public int $rubElHizbNumber,
        public int $rukuNumber,
        public ?int $manzilNumber = null,
        public ?int $sajdahNumber = null,
        public int $pageNumber = 1,
    ) {}
}
