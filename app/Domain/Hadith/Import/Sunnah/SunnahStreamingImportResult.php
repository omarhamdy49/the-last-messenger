<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahStreamingImportResult
{
    public function __construct(
        public string $slug,
        public string $snapshotHash,
        public int $books,
        public int $chapters,
        public int $hadiths,
        public int $publishedHadiths,
        public int $createdHadiths,
        public int $updatedHadiths,
        public int $missingArabic,
        public int $ungradedHadiths,
        public int $unmappedGrades,
        public bool $dryRun,
    ) {}
}
