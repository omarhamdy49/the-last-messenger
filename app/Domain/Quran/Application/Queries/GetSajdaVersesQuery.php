<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get all Sajda (prostration) verses
 */
final readonly class GetSajdaVersesQuery
{
    public function __construct(
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
