<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahCollectionSnapshot
{
    /** @param list<SunnahBookData> $books */
    public function __construct(
        public SunnahCollectionData $collection,
        public array $books,
    ) {}
}
