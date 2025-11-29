<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateSurahCommand;
use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Exceptions\SurahNotFoundException;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;

/**
 * Handler for updating a Surah
 */
final readonly class UpdateSurahHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(UpdateSurahCommand $command): SurahDTO
    {
        $surah = $this->surahRepository->find($command->id);

        if ($surah === null) {
            throw SurahNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'name_ar' => $command->nameAr,
            'name_en' => $command->nameEn,
            'transliteration' => $command->transliteration,
            'revelation_place' => $command->revelationPlace,
            'revelation_order' => $command->revelationOrder,
            'verses_count' => $command->versesCount,
            'bismillah_pre' => $command->bismillahPre,
            'pages' => $command->pages,
        ], fn ($value) => $value !== null);

        $this->surahRepository->update($command->id, $data);

        // Refresh the model
        $surah = $this->surahRepository->find($command->id);

        return SurahDTO::fromModel($surah);
    }
}
