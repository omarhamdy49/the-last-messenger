<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a random verse
 */
final readonly class GetRandomVerseQuery
{
    public function __construct(
        public string $locale = 'ar',
        public bool $withTranslations = true,
    ) {}
}
