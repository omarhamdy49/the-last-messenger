<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to list verses by Quran page number
 */
final readonly class ListVersesByPageQuery
{
    public function __construct(
        public int $page,
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
