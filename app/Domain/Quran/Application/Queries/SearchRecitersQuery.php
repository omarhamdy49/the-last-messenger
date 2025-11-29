<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to search reciters
 */
final readonly class SearchRecitersQuery
{
    public function __construct(
        public string $query,
        public bool $activeOnly = true,
        public string $locale = 'ar',
    ) {}
}
