<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahCollectionDefinition
{
    public function __construct(
        public string $source,
        public string $slug,
        public string $referenceTemplate,
        public int $sortOrder,
        public int $expectedHadiths,
        public bool $hasChapters,
    ) {}
}
