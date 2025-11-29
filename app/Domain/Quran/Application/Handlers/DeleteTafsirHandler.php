<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\DeleteTafsirCommand;
use App\Domain\Quran\Exceptions\TafsirNotFoundException;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;

/**
 * Handler for deleting a tafsir
 */
final readonly class DeleteTafsirHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
    ) {}

    public function handle(DeleteTafsirCommand $command): bool
    {
        $tafsir = $this->tafseerRepository->find($command->id);

        if ($tafsir === null) {
            throw TafsirNotFoundException::withId($command->id);
        }

        return $this->tafseerRepository->delete($command->id);
    }
}
