<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\ThemeDTO;
use App\Domain\Hadith\Application\Queries\ListThemesQuery;
use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for ListThemesQuery
 *
 * @implements QueryHandler<ListThemesQuery, PaginatedResult>
 */
final class ListThemesHandler implements QueryHandler
{
    public function __construct(
        private readonly ThemeRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param ListThemesQuery $query
     * @return PaginatedResult
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListThemesQuery $query */

        $filters = [];
        if ($query->onlyRoot) {
            $filters['only_root'] = true;
        }

        $paginator = $this->repository->paginateWithFilters(
            perPage: $query->perPage,
            search: $query->search ?? '',
            sortBy: $query->sortBy,
            filters: $filters
        );

        $locale = $query->locale ?? 'ar';
        $items = $paginator->getCollection()->map(
            fn($theme) => ThemeDTO::fromModel($theme, $locale, $query->withChildren)
        );

        return PaginatedResult::fromPaginator($paginator, $items->toArray());
    }
}
