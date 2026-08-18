<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\Queries\GetSurahByNumberQuery;
use App\Domain\Quran\Exceptions\SurahNotFoundException;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;

/**
 * Handler for getting a Surah by number
 */
final readonly class GetSurahByNumberHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(GetSurahByNumberQuery $query): SurahDTO
    {
        $surah = $this->surahRepository->getByNumber($query->number);

        if ($surah === null) {
            throw SurahNotFoundException::withNumber($query->number);
        }

        // The canonical revelation place: supplies a localized name and map pin
        // beside the unchanged مكية/مدنية classification. Named columns only.
        $surah->load([
            'revelationLocation:id,slug,latitude,longitude',
            'revelationLocation.translations' => fn ($relation) => $relation
                ->select(['id', 'location_id', 'locale', 'name'])
                ->whereIn('locale', array_unique([$query->locale, 'ar'])),
        ]);

        if ($query->withInfo) {
            $surah->load('info');
        }

        if ($query->withVerses) {
            $surah->load(['verses', 'verses.translations']);
        }

        return SurahDTO::fromModel($surah, $query->locale);
    }
}
