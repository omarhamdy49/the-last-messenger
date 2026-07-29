<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list all collections with optional filtering
 */
final readonly class ListCollectionsQuery implements Query
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        public int $perPage = 15,
        public int $page = 1,
        public ?string $search = null,
        public bool $onlyActive = true,
        public bool $withCounts = true,
        public string $sortBy = 'id',
        public string $sortDirection = 'asc',
        public array $filters = [],
        public ?string $locale = 'ar'
    ) {}
}
