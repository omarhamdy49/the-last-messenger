<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list verses by Juz number
 */
final readonly class ListVersesByJuzQuery
{
    public function __construct(
        public int $juz,
        public int $perPage = 50,
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
