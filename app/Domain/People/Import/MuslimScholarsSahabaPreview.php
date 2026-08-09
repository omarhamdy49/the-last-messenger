<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

final readonly class MuslimScholarsSahabaPreview
{
    /**
     * @param  list<array<string, string|null>>  $rows
     * @param  list<array<string, string|null>>  $reviewRows
     */
    public function __construct(
        public string $path,
        public string $datasetHash,
        public ?string $sourceSnapshotHash,
        public array $rows,
        public array $reviewRows,
        public int $arabicNames,
    ) {}

    public function total(): int
    {
        return count($this->rows) + count($this->reviewRows);
    }

    /** @return list<array<string, string|null>> */
    public function allRows(): array
    {
        return [...$this->rows, ...$this->reviewRows];
    }
}
