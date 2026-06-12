<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\Commands\DeleteHadithCommand;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Commands\Command;
use App\Domain\Shared\Application\Commands\CommandHandler;
use App\Domain\Shared\Application\DTOs\Result;
use Illuminate\Support\Facades\DB;

/**
 * Handler for DeleteHadithCommand
 *
 * @implements CommandHandler<DeleteHadithCommand, Result>
 */
final class DeleteHadithHandler implements CommandHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository,
    ) {}

    /**
     * Handle the command
     *
     * @param  DeleteHadithCommand  $command
     *
     * @throws HadithNotFoundException
     */
    public function handle(Command $command): Result
    {
        /** @var DeleteHadithCommand $command */
        $hadith = $this->repository->find($command->hadithId);
        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($command->hadithId);
        }

        DB::transaction(function () use ($command, $hadith) {
            // Delete related records
            $hadith->translations()->delete();
            $hadith->isnads()->delete();
            $hadith->gradings()->delete();
            $hadith->themes()->detach();

            if ($command->forceDelete) {
                $hadith->forceDelete();
            } else {
                $hadith->delete();
            }
        });

        return Result::success([
            'deleted' => true,
            'hadith_id' => $command->hadithId,
            'force_deleted' => $command->forceDelete,
        ], 'Hadith deleted successfully');
    }
}
