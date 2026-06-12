<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to resolve the public chapter (kitab) page context:
 * collection by slug + book by number, with adjacent-book navigation.
 */
final readonly class GetChapterContextQuery implements Query
{
    public function __construct(
        public string $collectionSlug,
        public int $bookNumber,
        public ?string $locale = 'ar',
    ) {}
}
