<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

final readonly class MuslimScholarsSahabaImportPlan
{
    /**
     * @param  list<array<string, string|null>>  $createRows
     * @param  list<array{row:array<string, string|null>,muslim_id:int,reason:string}>  $mergeRows
     * @param  list<array{row:array<string, string|null>,muslim_id:int}>  $refreshRows
     */
    public function __construct(
        public array $createRows,
        public array $mergeRows,
        public array $refreshRows,
    ) {}

    public function total(): int
    {
        return count($this->createRows) + count($this->mergeRows) + count($this->refreshRows);
    }
}
