<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\UpdateHadithCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Models\HadithIsnad;
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
            if ($command->gradingStatus !== null) {
                HadithGrading::query()->updateOrCreate(
                    ['hadith_id' => $hadith->id, 'scholar_id' => null],
                    ['grade_key' => $command->gradingStatus, 'raw_grade' => $command->gradingStatus],
                );
            }

            // Update chapter
            if ($command->chapterId !== null) {
                $hadith->chapter_id = $command->chapterId;
            }

            $hadith->save();

            if ($command->translations !== null) {
                foreach ($command->translations as $locale => $text) {
                    HadithTranslation::updateOrCreate(
                        [
                            'hadith_id' => $hadith->id,
                            'locale' => $locale,
                        ],
                        [
                            'text' => $text,
                        ]
                    );
                }

                if (isset($command->translations['ar'])) {
                    $hadith->text_hash = hash('sha256', trim($command->translations['ar']));
                    $hadith->save();
                }
            }

            if ($command->narratorIds !== null) {
                HadithIsnad::query()->where('hadith_id', $hadith->id)->delete();
                foreach (array_values(array_unique($command->narratorIds)) as $position => $narratorId) {
                    HadithIsnad::create([
                        'hadith_id' => $hadith->id,
                        'narrator_id' => $narratorId,
                        'position_in_chain' => $position + 1,
                    ]);
                }
                $hadith->primary_narrator_id = $command->narratorIds[0] ?? null;
                $hadith->save();
            }

            if ($command->themeIds !== null) {
                $hadith->themes()->sync($command->themeIds);
            }

            return $hadith;
        });

        $hadith->load(['translations', 'gradings', 'chapter.book.collection', 'themes']);

        return HadithDTO::fromModel($hadith);
    }
}
