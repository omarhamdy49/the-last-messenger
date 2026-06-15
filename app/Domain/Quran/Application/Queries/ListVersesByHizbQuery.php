<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list verses by Hizb number
 */
final readonly class ListVersesByHizbQuery
{
    public function __construct(
        public int $hizb,
        public int $perPage = 50,
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
