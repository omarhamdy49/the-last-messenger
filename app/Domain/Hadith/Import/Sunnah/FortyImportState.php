<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class FortyImportState
{
    /** @param array<string, int> $hadithCounts */
    public function __construct(public array $hadithCounts) {}

    public function countFor(string $slug): int
    {
        return $this->hadithCounts[$slug] ?? 0;
    }
}
