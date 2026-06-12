<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Queries;

use App\Domain\Shared\Application\Queries\Query;

/**
 * Query to get a hadith together with its parallel narrations
 * (other hadiths sharing the same normalized text hash).
 */
final readonly class GetParallelNarrationsQuery implements Query
{
    public function __construct(
        public int $hadithId,
        public ?string $locale = 'ar',
    ) {}
}
