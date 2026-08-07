<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Import\Sunnah;

final readonly class FortyImportResult
{
    /** @param list<FortyCollectionWriteResult> $collections */
    public function __construct(public array $collections) {}
}
