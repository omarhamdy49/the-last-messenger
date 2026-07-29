<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\UpdateGradingCommand;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Exceptions\InvalidGradingException;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithGrading;
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

        $hadith = DB::transaction(function () use ($command, $hadith) {
            HadithGrading::updateOrCreate(
                [
                    'hadith_id' => $hadith->id,
                    'scholar_id' => $command->scholarId,
                ],
                [
                    'grade_key' => $command->gradingStatus,
                    'raw_grade' => $command->gradingStatus,
                    'notes' => $command->notes,
                ]
            );

            return $hadith;
        });

        $hadith->load(['translations', 'chapter.book.collection', 'gradings']);

        return HadithDTO::fromModel($hadith);
    }
}
