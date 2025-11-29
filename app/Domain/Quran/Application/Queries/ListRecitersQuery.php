<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list reciters
 */
final readonly class ListRecitersQuery
{
    public function __construct(
        public int $perPage = 15,
        public bool $activeOnly = true,
        public ?string $country = null,
        public ?string $style = null,
        public bool $featuredOnly = false,
        public string $locale = 'ar',
    ) {}
}
