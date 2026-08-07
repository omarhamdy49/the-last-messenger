<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class FortyCollectionWriteResult
{
    public function __construct(
        public string $slug,
        public int $created,
        public int $updated,
        public int $total,
    ) {}
}
