<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list narrators with filtering
 */
final readonly class ListNarratorsQuery implements Query
{
    public function __construct(
        public int $perPage = 20,
        public int $page = 1,
        public ?string $search = null,
        public ?string $reliability = null,
        public bool $withHadithsCount = true,
        public string $sortBy = 'name_ar',
        public string $sortDirection = 'asc',
        public ?string $locale = 'ar'
    ) {}
}
