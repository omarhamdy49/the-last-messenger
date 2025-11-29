<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\CreateSurahInfoCommand;
use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use App\Domain\Quran\Exceptions\SurahNotFoundException;
use App\Domain\Quran\Models\SurahInfo;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;

/**
 * Handler for creating Surah info (asbab al-nuzul)
 */
final readonly class CreateSurahInfoHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(CreateSurahInfoCommand $command): SurahInfoDTO
    {
        // Verify surah exists
        $surah = $this->surahRepository->find($command->surahId);

        if ($surah === null) {
            throw SurahNotFoundException::withId($command->surahId);
        }

        $surahInfo = SurahInfo::create([
            'surah_id' => $command->surahId,
            'lang' => $command->lang,
            'desc' => $command->description,
            'content' => $command->content,
            'source' => $command->source,
            'active' => $command->isActive,
        ]);

        $surahInfo->load('surah');

        return SurahInfoDTO::fromModel($surahInfo);
    }
}
