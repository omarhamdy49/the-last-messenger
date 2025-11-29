<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateVerseCommand;
use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Exceptions\SurahNotFoundException;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for creating a verse (ayah)
 */
final readonly class CreateVerseHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(CreateVerseCommand $command): VerseDTO
    {
        // Verify surah exists
        $surah = $this->surahRepository->find($command->surahId);

        if ($surah === null) {
            throw SurahNotFoundException::withId($command->surahId);
        }

        $verse = $this->verseRepository->create([
            'surah_id' => $command->surahId,
            'verse_number' => $command->verseNumber,
            'verse_key' => $command->verseKey,
            'juz_number' => $command->juzNumber,
            'hizb_number' => $command->hizbNumber,
            'rub_el_hizb_number' => $command->rubElHizbNumber,
            'ruku_number' => $command->rukuNumber,
            'manzil_number' => $command->manzilNumber,
            'sajdah_number' => $command->sajdahNumber,
            'page_number' => $command->pageNumber,
        ]);

        return VerseDTO::fromModel($verse);
    }
}
