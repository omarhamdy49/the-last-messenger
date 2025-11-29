<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list tafsir by Surah
 */
final readonly class ListTafsirBySurahQuery
{
    public function __construct(
        public int $surahId,
        public int $perPage = 20,
        public string $locale = 'ar',
    ) {}
}
