<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to list hadiths by book
 */
final readonly class ListHadithsByBookQuery implements Query
{
    public function __construct(
        public int $bookId,
        public int $perPage = 20,
        public int $page = 1,
        public ?string $gradingStatus = null,
        public bool $withTranslations = true,
        public string $sortBy = 'hadith_number',
        public string $sortDirection = 'asc',
        public ?string $locale = 'ar'
    ) {}
}
