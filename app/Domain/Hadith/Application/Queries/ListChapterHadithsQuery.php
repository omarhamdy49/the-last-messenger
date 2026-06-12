<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list the hadiths of a public chapter (kitab/book) with
 * search-within-chapter and a normalized grading filter.
 */
final readonly class ListChapterHadithsQuery implements Query
{
    public function __construct(
        public int $bookId,
        public int $perPage = 15,
        public int $page = 1,
        public ?string $gradingStatus = null,
        public string $search = '',
        public ?string $locale = 'ar',
    ) {}
}
