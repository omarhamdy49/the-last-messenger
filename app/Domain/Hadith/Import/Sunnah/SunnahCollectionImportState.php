<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahCollectionImportState
{
    public function __construct(
        public int $books,
        public int $chapters,
        public int $hadiths,
    ) {}
}
