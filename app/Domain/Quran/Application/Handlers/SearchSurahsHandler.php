<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\Queries\SearchSurahsQuery;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for searching Surahs
 */
final readonly class SearchSurahsHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(SearchSurahsQuery $query): LengthAwarePaginator
    {
        $results = $this->surahRepository->search(
            $query->query,
            $query->perPage,
            $query->revelationPlace
        );

        // Transform items to DTOs while preserving pagination
        $results->getCollection()->transform(
            fn ($surah) => SurahDTO::fromModel($surah, $query->locale)
        );

        return $results;
    }
}
