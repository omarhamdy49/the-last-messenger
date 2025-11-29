<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a reciter by ID
 */
final readonly class GetReciterByIdQuery
{
    public function __construct(
        public int $id,
        public string $locale = 'ar',
    ) {}
}
