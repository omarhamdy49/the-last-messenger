<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to search Surahs
 */
final readonly class SearchSurahsQuery
{
    public function __construct(
        public string $query,
        public int $perPage = 15,
        public ?string $revelationPlace = null,
        public string $locale = 'ar',
    ) {}
}
