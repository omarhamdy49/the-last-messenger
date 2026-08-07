<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class FortyImportPreview
{
    /** @param list<MappedFortyCollection> $collections */
    public function __construct(
        public string $snapshotHash,
        public array $collections,
        public FortyImportState $existing,
    ) {}

    public function totalHadiths(): int
    {
        return array_sum(array_map(
            static fn (MappedFortyCollection $collection): int => count($collection->hadiths),
            $this->collections,
        ));
    }
}
