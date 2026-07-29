<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\CreateHadithCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithIsnad;
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
     * @param  CreateHadithCommand  $command
     *
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

        $chapter = $command->chapterId === null ? null : HadithChapter::query()->find($command->chapterId);
        if ($chapter === null || $chapter->book_id !== $command->bookId) {
            throw EntityNotFoundException::withCriteria('HadithChapter', [
                'id' => $command->chapterId,
                'book_id' => $command->bookId,
            ]);
        }

        // Validate grading status if provided
        if ($command->gradingStatus !== null && ! in_array($command->gradingStatus, Hadith::getGradingStatuses())) {
            throw InvalidGradingException::forStatus($command->gradingStatus);
        }

        // Check if hadith already exists with same book and number
        $existing = $this->hadithRepository->getByBookAndNumber($command->bookId, $command->hadithNumber);
        if ($existing !== null) {
            throw new \InvalidArgumentException(
                "Hadith with number {$command->hadithNumber} already exists in book {$command->bookId}"
            );
        }

        $primaryText = $command->translations['ar'] ?? reset($command->translations);
        if (! is_string($primaryText) || trim($primaryText) === '') {
            throw new \InvalidArgumentException('At least one non-empty hadith translation is required.');
        }

        $hadith = DB::transaction(function () use ($command, $primaryText) {
            // Create hadith
            $hadith = new Hadith([
                'chapter_id' => $command->chapterId,
                'primary_narrator_id' => $command->narratorIds[0] ?? null,
                'hadith_number' => $command->hadithNumber,
                'text_hash' => hash('sha256', trim($primaryText)),
            ]);
            $hadith->save();

            foreach ($command->translations as $locale => $text) {
                HadithTranslation::create([
                    'hadith_id' => $hadith->id,
                    'locale' => $locale,
                    'text' => $text,
                ]);
            }

            foreach (array_values(array_unique($command->narratorIds)) as $position => $narratorId) {
                HadithIsnad::create([
                    'hadith_id' => $hadith->id,
                    'narrator_id' => $narratorId,
                    'position_in_chain' => $position + 1,
                ]);
            }

            if (! empty($command->themeIds)) {
                $hadith->themes()->attach($command->themeIds);
            }

            if ($command->gradingStatus !== null) {
                HadithGrading::create([
                    'hadith_id' => $hadith->id,
                    'grade_key' => $command->gradingStatus,
                    'raw_grade' => $command->gradingStatus,
                ]);
            }

            return $hadith;
        });

        $hadith->load(['translations', 'gradings', 'chapter.book.collection', 'themes']);

        return HadithDTO::fromModel($hadith);
    }
}
