<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class MappedFortyCollection
{
    /** @param list<MappedHadithData> $hadiths */
    public function __construct(
        public string $sourceBookNumber,
        public string $slug,
        public string $titleAr,
        public string $titleEn,
        public string $referenceTemplate,
        public int $sortOrder,
        public int $expectedHadiths,
        public array $hadiths,
        public int $missingArabic,
        public int $ungradedHadiths,
        public int $unmappedGrades,
    ) {}
}
