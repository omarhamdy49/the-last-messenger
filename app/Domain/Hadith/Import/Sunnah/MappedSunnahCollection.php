<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class MappedSunnahCollection
{
    /** @param list<MappedSunnahBook> $books */
    public function __construct(
        public string $source,
        public string $slug,
        public string $titleAr,
        public string $titleEn,
        public ?string $introAr,
        public ?string $introEn,
        public string $referenceTemplate,
        public int $sortOrder,
        public int $expectedHadiths,
        public bool $hasChapters,
        public array $books,
        public int $missingArabic,
        public int $ungradedHadiths,
        public int $unmappedGrades,
    ) {}

    public function hadithCount(): int
    {
        return array_sum(array_map(
            static fn (MappedSunnahBook $book): int => count($book->hadiths),
            $this->books,
        ));
    }

    public function chapterCount(): int
    {
        return array_sum(array_map(
            static fn (MappedSunnahBook $book): int => count($book->chapters),
            $this->books,
        ));
    }
}
