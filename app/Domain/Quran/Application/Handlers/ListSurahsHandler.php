<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\Queries\ListSurahsQuery;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for listing Surahs
 */
final readonly class ListSurahsHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    /**
     * @return Collection<int, SurahDTO>
     */
    public function handle(ListSurahsQuery $query): Collection
    {
        $surahs = match (true) {
            $query->revelationPlace !== null => $this->surahRepository->getByRevelationPlace($query->revelationPlace),
            $query->juz !== null => $this->surahRepository->getByJuz($query->juz),
            default => $this->surahRepository->all(),
        };

        // Sort the collection
        $surahs = match ($query->sortDirection) {
            'desc' => $surahs->sortByDesc($query->sortBy),
            default => $surahs->sortBy($query->sortBy),
        };

        return $surahs->values()->map(
            fn ($surah) => SurahDTO::fromModel($surah, $query->locale)
        );
    }
}
