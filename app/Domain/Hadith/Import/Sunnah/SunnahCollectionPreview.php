<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class SunnahCollectionPreview
{
    public function __construct(
        public string $snapshotHash,
        public MappedSunnahCollection $collection,
        public SunnahCollectionImportState $existing,
    ) {}
}
