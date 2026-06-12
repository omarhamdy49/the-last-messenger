<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list revelation contexts (Asbab al-Nuzul) with filters
 */
final readonly class ListRevelationContextsQuery
{
    public function __construct(
        public ?string $period = null,
        public ?int $surahId = null,
        public ?string $search = null,
        public string $sort = 'mushaf',
        public int $perPage = 10,
        public bool $activeOnly = true,
        public string $locale = 'ar',
        public bool $withExcerpts = false,
    ) {}
}
