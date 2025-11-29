<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get Surah info (asbab al-nuzul)
 */
final readonly class GetSurahInfoQuery
{
    public function __construct(
        public int $surahId,
        public string $lang = 'ar',
    ) {}
}
