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
        public int $alreadyImported,
        public int $arabicNames,
    ) {}

    public function total(): int
    {
        return count($this->rows) + count($this->reviewRows);
    }

    public function importable(): int
    {
        return count($this->rows);
    }

    public function toCreate(): int
    {
        return $this->importable() - $this->alreadyImported;
    }
}
