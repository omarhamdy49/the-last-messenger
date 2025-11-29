<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a single Collection by ID
 */
final readonly class GetCollectionByIdQuery implements Query
{
    public function __construct(
        public int $collectionId,
        public bool $withBooks = false,
        public ?string $locale = 'ar'
    ) {}
}
