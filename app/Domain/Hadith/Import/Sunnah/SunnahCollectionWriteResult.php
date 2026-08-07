<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahCollectionWriteResult
{
    public function __construct(
        public string $slug,
        public int $books,
        public int $chapters,
        public int $createdHadiths,
        public int $updatedHadiths,
        public int $totalHadiths,
        public int $publishedHadiths,
    ) {}
}
