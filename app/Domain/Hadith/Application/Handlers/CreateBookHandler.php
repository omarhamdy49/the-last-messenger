<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateBookCommand;
use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for CreateBookCommand
 *
 * @implements CommandHandler<CreateBookCommand, BookDTO>
 */
final class CreateBookHandler implements CommandHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly CollectionRepositoryInterface $collectionRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  CreateBookCommand  $command
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): BookDTO
    {
        /** @var CreateBookCommand $command */

        // Validate collection exists
        $collection = $this->collectionRepository->find($command->collectionId);
        if ($collection === null) {
            throw EntityNotFoundException::forEntity('HadithCollection', $command->collectionId);
        }

        // Check for duplicate book number in collection
        $existing = $this->bookRepository->getByCollectionAndNumber(
            $command->collectionId,
            $command->bookNumber
        );
        if ($existing !== null) {
            throw ValidationException::withErrors([
                'book_number' => ["Book number {$command->bookNumber} already exists in this collection"],
            ]);
        }

        $book = HadithBook::create([
            'collection_id' => $command->collectionId,
            'title_ar' => $command->title['ar'] ?? '',
            'title_en' => $command->title['en'] ?? null,
            'slug_ar' => $command->slug,
            'slug_en' => $command->slug,
            'book_number' => $command->bookNumber,
            'chapter_title_ar' => $command->chapterTitle['ar'] ?? null,
            'chapter_title_en' => $command->chapterTitle['en'] ?? null,
            'hadith_count' => $command->hadithCount,
        ]);

        return BookDTO::fromModel($book);
    }
}
