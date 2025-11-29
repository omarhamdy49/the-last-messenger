<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\SearchHadithQuery;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for SearchHadithQuery
 *
 * @implements QueryHandler<SearchHadithQuery, PaginatedResult<HadithDTO>>
 */
final class SearchHadithHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param SearchHadithQuery $query
     * @return PaginatedResult<HadithDTO>
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var SearchHadithQuery $query */
        $paginator = $this->repository->paginateWithFilters(
            perPage: $query->perPage,
            search: $query->searchTerm,
            sortBy: $query->sortBy,
            filters: $query->filters
        );

        // Transform models to DTOs
        $locale = $query->filters['locale'] ?? 'ar';
        $items = collect($paginator->items())
            ->map(fn ($hadith) => HadithDTO::fromModel($hadith, $locale))
            ->toArray();

        return new PaginatedResult(
            items: $items,
            total: $paginator->total(),
            perPage: $paginator->perPage(),
            currentPage: $paginator->currentPage(),
            lastPage: $paginator->lastPage(),
            hasMorePages: $paginator->hasMorePages()
        );
    }
}
