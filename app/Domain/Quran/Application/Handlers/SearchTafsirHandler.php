<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Application\Queries\SearchTafsirQuery;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for searching tafsir
 */
final readonly class SearchTafsirHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
    ) {}

    public function handle(SearchTafsirQuery $query): LengthAwarePaginator
    {
        $results = $this->tafseerRepository->search($query->query, $query->perPage);

        $results->getCollection()->transform(
            fn ($tafsir) => TafsirDTO::fromModel($tafsir, $query->locale)
        );

        return $results;
    }
}
