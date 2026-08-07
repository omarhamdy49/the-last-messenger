<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class MappedSunnahChapter
{
    public function __construct(
        public string $sourceId,
        public string $number,
        public string $titleAr,
        public ?string $titleEn,
        public ?string $introAr,
        public ?string $introEn,
        public ?string $endingAr,
        public ?string $endingEn,
        public int $sortOrder,
    ) {}
}
