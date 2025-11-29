<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list all Surahs
 */
final readonly class ListSurahsQuery
{
    public function __construct(
        public string $locale = 'ar',
        public ?string $revelationPlace = null,
        public ?int $juz = null,
        public string $sortBy = 'id',
        public string $sortDirection = 'asc',
    ) {}
}
