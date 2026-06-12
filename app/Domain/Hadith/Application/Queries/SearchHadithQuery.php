<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to search Hadiths
 */
final readonly class SearchHadithQuery implements Query
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public string $searchTerm = '',
        public int $page = 1,
        public int $perPage = 15,
        public array $filters = [],
        public string $sortBy = 'hadith_number',
        public string $sortDirection = 'asc'
    ) {}
}
