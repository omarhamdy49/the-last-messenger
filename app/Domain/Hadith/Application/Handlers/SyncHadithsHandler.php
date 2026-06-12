<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\SyncHadithsCommand;
use App\Domain\Hadith\Models\HadithSyncLog;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Services\HadithSyncService;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Application\DTOs\Result;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for SyncHadithsCommand
 *
 * Delegates to HadithSyncService for actual sync logic
 *
 * @implements CommandHandler<SyncHadithsCommand, Result>
 */
final class SyncHadithsHandler implements CommandHandler
{
    public function __construct(
        private readonly HadithSyncService $syncService,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  SyncHadithsCommand  $command
     *
     * @throws EntityNotFoundException
     */
    public function handle(Command $command): Result
    {
        /** @var SyncHadithsCommand $command */

        // Validate book exists
        $book = $this->bookRepository->find($command->bookId);
        if ($book === null) {
            throw EntityNotFoundException::forEntity('HadithBook', $command->bookId);
        }

        // Get sync log if provided
        $syncLog = null;
        if ($command->syncLogId !== null) {
            $syncLog = HadithSyncLog::find($command->syncLogId);
        }

        try {
            $syncedCount = $this->syncService->syncBookHadiths(
                $book,
                $command->collectionName,
                $command->bookNumber,
                $syncLog,
                $command->dryRun
            );

            return Result::success([
                'synced_count' => $syncedCount,
                'book_id' => $command->bookId,
                'collection' => $command->collectionName,
                'book_number' => $command->bookNumber,
                'dry_run' => $command->dryRun,
            ], "Successfully synced {$syncedCount} hadiths");
        } catch (\Exception $e) {
            return Result::failure(
                "Sync failed: {$e->getMessage()}",
                [
                    'book_id' => $command->bookId,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
