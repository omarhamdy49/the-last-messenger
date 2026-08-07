<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class MappedSunnahBookResult
{
    public function __construct(
        public MappedSunnahBook $book,
        public int $missingArabic,
        public int $ungradedHadiths,
        public int $unmappedGrades,
    ) {}
}
