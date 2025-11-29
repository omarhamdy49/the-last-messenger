<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list verses by Surah
 */
final readonly class ListVersesBySurahQuery
{
    public function __construct(
        public int $surahId,
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
