<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a verse by key (e.g., "2:255" for Ayatul Kursi)
 */
final readonly class GetVerseByKeyQuery
{
    public function __construct(
        public int $surahNumber,
        public int $verseNumber,
        public string $locale = 'ar',
        public bool $withTranslations = false,
        public bool $withTafsir = false,
    ) {}
}
