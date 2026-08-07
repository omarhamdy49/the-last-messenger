<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

use App\Domain\Hadith\Enums\Grade;

final readonly class MappedHadithData
{
    public function __construct(
        public string $referenceKey,
        public string $number,
        public int $sortOrder,
        public ?Grade $grade,
        public ?string $rawGrade,
        public ?string $textAr,
        public ?string $textEn,
        public ?string $searchAr,
        public ?string $searchEn,
        public string $canonicalReference,
        public ?string $bookNumber = null,
        public ?string $chapterSourceId = null,
        public ?string $inBookNumber = null,
        public ?string $inBookReference = null,
    ) {}
}
