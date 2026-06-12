<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\UpdateGradingCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithGrading;
use App\Domain\Hadith\Repositories\GradingLevelRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;
use Illuminate\Support\Facades\DB;

/**
 * Handler for UpdateGradingCommand
 *
 * @implements CommandHandler<UpdateGradingCommand, HadithDTO>
 */
final class UpdateGradingHandler implements CommandHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly ScholarRepositoryInterface $scholarRepository,
        private readonly GradingLevelRepositoryInterface $gradingLevelRepository,
    ) {}

    /**
     * Handle the command
     *
     * @param  UpdateGradingCommand  $command
     *
     * @throws HadithNotFoundException
     * @throws InvalidGradingException
     * @throws EntityNotFoundException
     */
    public function handle(Command $command): HadithDTO
    {
        /** @var UpdateGradingCommand $command */

        // Validate hadith exists
        $hadith = $this->hadithRepository->find($command->hadithId);
        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($command->hadithId);
        }

        // Validate grading status
        if (! in_array($command->gradingStatus, Hadith::getGradingStatuses())) {
            throw InvalidGradingException::forStatus($command->gradingStatus);
        }

        // Validate scholar if provided
        if ($command->scholarId !== null) {
            $scholar = $this->scholarRepository->find($command->scholarId);
            if ($scholar === null) {
                throw EntityNotFoundException::forEntity('HadithScholar', $command->scholarId);
            }
        }

        // Validate grading level if provided
        if ($command->gradingLevelId !== null) {
            $gradingLevel = $this->gradingLevelRepository->find($command->gradingLevelId);
            if ($gradingLevel === null) {
                throw EntityNotFoundException::forEntity('HadithGradingLevel', $command->gradingLevelId);
            }
        }

        $hadith = DB::transaction(function () use ($command, $hadith) {
            // Update the overall grading status on hadith
            $hadith->grading_status = $command->gradingStatus;
            $hadith->save();

            // Add a grading record if scholar is provided
            if ($command->scholarId !== null) {
                HadithGrading::updateOrCreate(
                    [
                        'hadith_id' => $hadith->id,
                        'scholar_id' => $command->scholarId,
                    ],
                    [
                        'grading_level_id' => $command->gradingLevelId,
                        'notes' => $command->notes,
                    ]
                );

                // Recalculate agreement status based on all gradings
                $this->recalculateAgreement($hadith);
            }

            return $hadith;
        });

        // Reload with relations
        $hadith->load(['translations', 'book', 'gradings']);

        return HadithDTO::fromModel($hadith);
    }

    /**
     * Recalculate grading agreement based on all scholar gradings
     */
    private function recalculateAgreement(Hadith $hadith): void
    {
        $gradings = $hadith->gradings()->get();

        if ($gradings->count() < 2) {
            $hadith->grading_agreement = null;
            $hadith->save();

            return;
        }

        // Check if all gradings have the same level
        $levels = $gradings->pluck('grading_level_id')->unique();
        $hadith->grading_agreement = $levels->count() === 1;
        $hadith->save();
    }
}
