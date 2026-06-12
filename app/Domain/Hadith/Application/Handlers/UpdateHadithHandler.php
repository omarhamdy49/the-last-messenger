<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\UpdateHadithCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use Illuminate\Support\Facades\DB;

/**
 * Handler for UpdateHadithCommand
 *
 * @implements CommandHandler<UpdateHadithCommand, HadithDTO>
 */
final class UpdateHadithHandler implements CommandHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository,
    ) {}

    /**
     * Handle the command
     *
     * @param  UpdateHadithCommand  $command
     *
     * @throws HadithNotFoundException
     * @throws InvalidGradingException
     */
    public function handle(Command $command): HadithDTO
    {
        /** @var UpdateHadithCommand $command */
        $hadith = $this->repository->find($command->hadithId);
        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($command->hadithId);
        }

        // Validate grading status if provided
        if ($command->gradingStatus !== null && ! in_array($command->gradingStatus, Hadith::getGradingStatuses())) {
            throw InvalidGradingException::forStatus($command->gradingStatus);
        }

        $hadith = DB::transaction(function () use ($command, $hadith) {
            // Update grading status
            if ($command->gradingStatus !== null) {
                $hadith->grading_status = $command->gradingStatus;
            }

            // Update chapter
            if ($command->chapterId !== null) {
                $hadith->chapter_id = $command->chapterId;
            }

            $hadith->save();

            // Update translations
            if ($command->translations !== null) {
                foreach ($command->translations as $languageCode => $text) {
                    HadithTranslation::updateOrCreate(
                        [
                            'hadith_id' => $hadith->id,
                            'language_code' => $languageCode,
                        ],
                        [
                            'matn' => $text,
                        ]
                    );
                }
            }

            // Sync narrators
            if ($command->narratorIds !== null) {
                $hadith->narrators()->sync($command->narratorIds);
            }

            // Sync themes
            if ($command->themeIds !== null) {
                $hadith->themes()->sync($command->themeIds);
            }

            return $hadith;
        });

        // Reload with relations
        $hadith->load(['translations', 'book', 'themes']);

        return HadithDTO::fromModel($hadith);
    }
}
