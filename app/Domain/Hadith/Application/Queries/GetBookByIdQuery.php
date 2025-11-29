<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a single Book by ID
 */
final readonly class GetBookByIdQuery implements Query
{
    public function __construct(
        public int $bookId,
        public bool $withChapters = false,
        public bool $withCollection = true,
        public ?string $locale = 'ar'
    ) {}
}
