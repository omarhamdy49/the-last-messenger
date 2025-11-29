<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a Surah by number (1-114)
 */
final readonly class GetSurahByNumberQuery
{
    public function __construct(
        public int $number,
        public string $locale = 'ar',
        public bool $withInfo = false,
        public bool $withVerses = false,
    ) {}
}
