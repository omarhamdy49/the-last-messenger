<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to search verses
 */
final readonly class SearchVersesQuery
{
    public function __construct(
        public string $query,
        public int $perPage = 20,
        public ?int $surahId = null,
        public string $language = 'ar',
        public bool $searchArabic = true,
        public bool $searchTranslation = true,
    ) {}
}
