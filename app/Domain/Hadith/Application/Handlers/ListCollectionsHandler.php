<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\Queries\ListCollectionsQuery;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for ListCollectionsQuery
 *
 * @implements QueryHandler<ListCollectionsQuery, PaginatedResult>
 */
final class ListCollectionsHandler implements QueryHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param ListCollectionsQuery $query
     * @return PaginatedResult
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListCollectionsQuery $query */
        $paginator = $this->repository->paginateWithFilters(
            perPage: $query->perPage,
            search: $query->search ?? '',
            sortBy: $query->sortBy,
            filters: array_merge($query->filters, [
                'only_active' => $query->onlyActive,
            ])
        );

        $locale = $query->locale ?? 'ar';
        $items = $paginator->getCollection()->map(
            fn($collection) => CollectionDTO::fromModel($collection, $locale)
        );

        return PaginatedResult::fromPaginator($paginator, $items->toArray());
    }
}
