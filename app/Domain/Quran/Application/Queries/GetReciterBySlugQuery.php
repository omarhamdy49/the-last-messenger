<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a reciter by slug
 */
final readonly class GetReciterBySlugQuery
{
    public function __construct(
        public string $slug,
        public string $locale = 'ar',
    ) {}
}
