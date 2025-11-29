<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateVerseCommand;
use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for updating a verse (ayah)
 */
final readonly class UpdateVerseHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(UpdateVerseCommand $command): VerseDTO
    {
        $verse = $this->verseRepository->find($command->id);

        if ($verse === null) {
            throw VerseNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'juz_number' => $command->juzNumber,
            'hizb_number' => $command->hizbNumber,
            'rub_el_hizb_number' => $command->rubElHizbNumber,
            'ruku_number' => $command->rukuNumber,
            'manzil_number' => $command->manzilNumber,
            'sajdah_number' => $command->sajdahNumber,
            'page_number' => $command->pageNumber,
        ], fn ($value) => $value !== null);

        $this->verseRepository->update($command->id, $data);

        // Refresh the model
        $verse = $this->verseRepository->find($command->id);

        return VerseDTO::fromModel($verse);
    }
}
