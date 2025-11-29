<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a single Collection by slug
 */
final readonly class GetCollectionBySlugQuery implements Query
{
    public function __construct(
        public string $slug,
        public bool $withBooks = false,
        public ?string $locale = 'ar'
    ) {}
}
