<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to import hadiths from JSON file
 */
final readonly class ImportHadithsCommand implements Command
{
    /**
     * @param string $filePath Path to the JSON file
     * @param int $collectionId The collection ID to import into
     * @param bool $validateOnly If true, only validate without persisting
     * @param bool $skipExisting If true, skip hadiths that already exist
     */
    public function __construct(
        public string $filePath,
        public int $collectionId,
        public bool $validateOnly = false,
        public bool $skipExisting = true,
    ) {}
}
