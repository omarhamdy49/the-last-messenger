<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateTafsirCommand;
use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Exceptions\TafsirNotFoundException;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;

/**
 * Handler for updating a tafsir (verse interpretation)
 */
final readonly class UpdateTafsirHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
    ) {}

    public function handle(UpdateTafsirCommand $command): TafsirDTO
    {
        $tafsir = $this->tafseerRepository->find($command->id);

        if ($tafsir === null) {
            throw TafsirNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'lang' => $command->lang,
            'text' => $command->text,
            'active' => $command->isActive,
        ], fn ($value) => $value !== null);

        $this->tafseerRepository->update($command->id, $data);

        // Refresh the model
        $tafsir = $this->tafseerRepository->find($command->id);

        return TafsirDTO::fromModel($tafsir);
    }
}
