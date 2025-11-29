<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a single Hadith by ID
 */
final readonly class GetHadithByIdQuery implements Query
{
    public function __construct(
        public int $hadithId,
        public bool $withRelations = true,
        public ?string $locale = 'ar'
    ) {}
}
