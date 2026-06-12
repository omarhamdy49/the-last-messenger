<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Commands;

use App\Domain\Shared\Application\Commands\Command;

/**
 * Command to sync hadiths from external API
 */
final readonly class SyncHadithsCommand implements Command
{
    /**
     * @param  int  $bookId  The book ID to sync hadiths for
     * @param  string  $collectionName  The API collection name (e.g., 'bukhari')
     * @param  string  $bookNumber  The API book number
     * @param  bool  $dryRun  If true, only validate without persisting
     * @param  int|null  $syncLogId  Optional sync log ID for progress tracking
     */
    public function __construct(
        public int $bookId,
        public string $collectionName,
        public string $bookNumber,
        public bool $dryRun = false,
        public ?int $syncLogId = null,
    ) {}
}
