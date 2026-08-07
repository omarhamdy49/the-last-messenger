<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class MappedSunnahBook
{
    /**
     * @param  list<MappedSunnahChapter>  $chapters
     * @param  list<MappedHadithData>  $hadiths
     */
    public function __construct(
        public string $number,
        public string $titleAr,
        public string $titleEn,
        public ?string $firstHadithNumber,
        public ?string $lastHadithNumber,
        public int $sortOrder,
        public array $chapters,
        public array $hadiths,
    ) {}
}
