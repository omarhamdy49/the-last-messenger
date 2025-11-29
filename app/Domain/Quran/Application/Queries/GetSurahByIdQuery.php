<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Queries;

/**
 * Query to get a Surah by ID
 */
final readonly class GetSurahByIdQuery
{
    public function __construct(
        public int $id,
        public string $locale = 'ar',
        public bool $withInfo = false,
        public bool $withVerses = false,
    ) {}
}
