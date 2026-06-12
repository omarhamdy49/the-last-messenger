<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateChapterCommand;
use App\Domain\Hadith\Application\DTOs\ChapterDTO;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\ChapterRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use App\Domain\Shared\Exceptions\ValidationException;

/**
 * Handler for CreateChapterCommand
 *
 * @implements CommandHandler<CreateChapterCommand, ChapterDTO>
 */
final class CreateChapterHandler implements CommandHandler
{
    public function __construct(
        private readonly ChapterRepositoryInterface $chapterRepository,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  CreateChapterCommand  $command
     *
     * @throws EntityNotFoundException
     * @throws ValidationException
     */
    public function handle(Command $command): ChapterDTO
    {
        /** @var CreateChapterCommand $command */

        // Validate book exists
        $book = $this->bookRepository->find($command->bookId);
        if ($book === null) {
            throw EntityNotFoundException::forEntity('HadithBook', $command->bookId);
        }

        // Check for duplicate chapter number in book
        $existing = $this->chapterRepository->getByBookAndNumber(
            $command->bookId,
            $command->chapterNumber
        );
        if ($existing !== null) {
            throw ValidationException::withErrors([
                'chapter_number' => ["Chapter number {$command->chapterNumber} already exists in this book"],
            ]);
        }

        $chapter = HadithChapter::create([
            'book_id' => $command->bookId,
            'title_ar' => $command->title['ar'] ?? '',
            'title_en' => $command->title['en'] ?? null,
            'chapter_number' => $command->chapterNumber,
            'intro_ar' => $command->intro['ar'] ?? null,
            'intro_en' => $command->intro['en'] ?? null,
        ]);

        return ChapterDTO::fromModel($chapter);
    }
}
