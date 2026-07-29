<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list themes with filtering
 */
final readonly class ListThemesQuery implements Query
{
    public function __construct(
        public int $perPage = 50,
        public int $page = 1,
        public ?string $search = null,
        public bool $withHadithsCount = true,
        public string $sortBy = 'id',
        public string $sortDirection = 'asc',
        public ?string $locale = 'ar'
    ) {}
}
