<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateHadithCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Handler for CreateHadithCommand
 *
 * @implements CommandHandler<CreateHadithCommand, HadithDTO>
 */
final class CreateHadithHandler implements CommandHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param CreateHadithCommand $command
     * @return HadithDTO
     * @throws EntityNotFoundException
     * @throws InvalidGradingException
     */
    public function handle(Command $command): HadithDTO
    {
        /** @var CreateHadithCommand $command */

        // Validate book exists
        $book = $this->bookRepository->find($command->bookId);
        if ($book === null) {
            throw EntityNotFoundException::forEntity('HadithBook', $command->bookId);
        }

        // Validate grading status if provided
        if ($command->gradingStatus !== null && !in_array($command->gradingStatus, Hadith::getGradingStatuses())) {
            throw InvalidGradingException::forStatus($command->gradingStatus);
        }

        // Check if hadith already exists with same book and number
        $existing = $this->hadithRepository->getByBookAndNumber($command->bookId, $command->hadithNumber);
        if ($existing !== null) {
            throw new \InvalidArgumentException(
                "Hadith with number {$command->hadithNumber} already exists in book {$command->bookId}"
            );
        }

        $hadith = DB::transaction(function () use ($command) {
            // Create hadith
            $hadith = new Hadith([
                'book_id' => $command->bookId,
                'hadith_number' => $command->hadithNumber,
                'grading_status' => $command->gradingStatus ?? 'unknown',
            ]);

            if ($command->chapterId !== null) {
                $hadith->chapter_id = $command->chapterId;
            }

            $hadith->save();

            // Create translations
            foreach ($command->translations as $languageCode => $text) {
                HadithTranslation::create([
                    'hadith_id' => $hadith->id,
                    'language_code' => $languageCode,
                    'matn' => $text,
                ]);
            }

            // Attach narrators
            if (!empty($command->narratorIds)) {
                $hadith->narrators()->attach($command->narratorIds);
            }

            // Attach themes
            if (!empty($command->themeIds)) {
                $hadith->themes()->attach($command->themeIds);
            }

            return $hadith;
        });

        // Load relations and return DTO
        $hadith->load(['translations', 'book', 'themes']);

        return HadithDTO::fromModel($hadith);
    }
}
