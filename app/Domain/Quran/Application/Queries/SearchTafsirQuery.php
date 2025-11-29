<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to search tafsir
 */
final readonly class SearchTafsirQuery
{
    public function __construct(
        public string $query,
        public int $perPage = 20,
        public string $locale = 'ar',
    ) {}
}
