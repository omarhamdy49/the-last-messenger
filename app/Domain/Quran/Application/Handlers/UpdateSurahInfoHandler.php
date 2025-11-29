<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\Commands\UpdateSurahInfoCommand;
use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use App\Domain\Quran\Exceptions\SurahInfoNotFoundException;
use App\Domain\Quran\Models\SurahInfo;

/**
 * Handler for updating Surah info (asbab al-nuzul)
 */
final readonly class UpdateSurahInfoHandler
{
    public function handle(UpdateSurahInfoCommand $command): SurahInfoDTO
    {
        $surahInfo = SurahInfo::find($command->id);

        if ($surahInfo === null) {
            throw SurahInfoNotFoundException::withId($command->id);
        }

        $data = array_filter([
            'lang' => $command->lang,
            'desc' => $command->description,
            'content' => $command->content,
            'source' => $command->source,
            'active' => $command->isActive,
        ], fn ($value) => $value !== null);

        $surahInfo->update($data);
        $surahInfo->load('surah');

        return SurahInfoDTO::fromModel($surahInfo);
    }
}
