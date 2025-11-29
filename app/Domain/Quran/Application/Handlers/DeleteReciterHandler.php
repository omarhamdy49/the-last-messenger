<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\DeleteReciterCommand;
use App\Domain\Quran\Exceptions\ReciterNotFoundException;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;

/**
 * Handler for deleting a Quran reciter
 */
final readonly class DeleteReciterHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(DeleteReciterCommand $command): bool
    {
        $reciter = $this->reciterRepository->getById($command->id);

        if ($reciter === null) {
            throw ReciterNotFoundException::withId($command->id);
        }

        return $this->reciterRepository->delete($reciter);
    }
}
