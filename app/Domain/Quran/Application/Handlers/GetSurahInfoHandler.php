<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\SurahInfoDTO;
use App\Domain\Quran\Application\Queries\GetSurahInfoQuery;
use App\Domain\Quran\Exceptions\SurahInfoNotFoundException;
use App\Domain\Quran\Models\SurahInfo;

/**
 * Handler for getting Surah info (asbab al-nuzul)
 */
final readonly class GetSurahInfoHandler
{
    public function handle(GetSurahInfoQuery $query): SurahInfoDTO
    {
        $info = SurahInfo::where('surah_id', $query->surahId)
            ->where('lang', $query->lang)
            ->with('surah')
            ->first();

        if ($info === null) {
            throw SurahInfoNotFoundException::forSurahAndLang($query->surahId, $query->lang);
        }

        return SurahInfoDTO::fromModel($info, $query->lang);
    }
}
