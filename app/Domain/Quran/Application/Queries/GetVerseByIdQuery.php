<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a verse by ID
 */
final readonly class GetVerseByIdQuery
{
    public function __construct(
        public int $id,
        public string $locale = 'ar',
        public bool $withTranslations = false,
        public bool $withTafsir = false,
    ) {}
}
