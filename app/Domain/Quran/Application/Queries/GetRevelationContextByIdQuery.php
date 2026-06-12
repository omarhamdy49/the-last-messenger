<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a single revelation context (Asbab al-Nuzul) by ID
 */
final readonly class GetRevelationContextByIdQuery
{
    public function __construct(
        public int $id,
    ) {}
}
