<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list books by collection
 */
final readonly class ListBooksByCollectionQuery implements Query
{
    public function __construct(
        public int $collectionId,
        public int $perPage = 15,
        public int $page = 1,
        public bool $withChaptersCount = true,
        public bool $withHadithsCount = true,
        public string $sortBy = 'book_number',
        public string $sortDirection = 'asc',
        public ?string $locale = 'ar'
    ) {}
}
