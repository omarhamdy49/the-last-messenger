<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\ImportHadithsCommand;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Hadith\Services\JsonImportService;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Application\DTOs\Result;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for ImportHadithsCommand
 *
 * Delegates to JsonImportService for actual import logic
 *
 * @implements CommandHandler<ImportHadithsCommand, Result>
 */
final class ImportHadithsHandler implements CommandHandler
{
    public function __construct(
        private readonly JsonImportService $importService,
        private readonly CollectionRepositoryInterface $collectionRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  ImportHadithsCommand  $command
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): Result
    {
        /** @var ImportHadithsCommand $command */

        // Validate file exists
        if (! file_exists($command->filePath)) {
            throw ValidationException::withErrors([
                'file_path' => ["File not found: {$command->filePath}"],
            ]);
        }

        // Validate collection exists
        $collection = $this->collectionRepository->find($command->collectionId);
        if ($collection === null) {
            throw EntityNotFoundException::forEntity('HadithCollection', $command->collectionId);
        }

        try {
            $result = $this->importService->importFromJson(
                $command->filePath,
                $collection,
                $command->validateOnly,
                $command->skipExisting
            );

            $message = $command->validateOnly
                ? "Validation complete. {$result['valid_count']} valid records found."
                : "Import complete. {$result['imported_count']} hadiths imported.";

            return Result::success($result, $message);
        } catch (\Exception $e) {
            return Result::failure(
                "Import failed: {$e->getMessage()}",
                [
                    'file_path' => $command->filePath,
                    'collection_id' => $command->collectionId,
                    'error' => $e->getMessage(),
                ]
            );
        }
    }
}
