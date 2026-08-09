<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

final readonly class MuslimScholarsSahabaImportResult
{
    public function __construct(
        public int $created,
        public int $mergedExisting,
        public int $refreshedImported,
    ) {}
}
