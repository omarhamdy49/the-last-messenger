<?php

declare(strict_types=1);

namespace App\Domain\People\Import;

final readonly class MuslimScholarsSahabaResolutionResult
{
    public function __construct(
        public int $mergedIntoExisting,
        public int $createdAsDistinct,
        public int $alreadyResolved,
        public int $genderFilled,
        public int $profilesFilled,
        public int $metadataSynced,
    ) {}
}
