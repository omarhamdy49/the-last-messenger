<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list chapters by book
 */
final readonly class ListChaptersByBookQuery implements Query
{
    public function __construct(
        public int $bookId,
        public int $perPage = 50,
        public int $page = 1,
        public bool $withHadithsCount = true,
        public string $sortBy = 'chapter_number',
        public string $sortDirection = 'asc',
        public ?string $locale = 'ar'
    ) {}
}
