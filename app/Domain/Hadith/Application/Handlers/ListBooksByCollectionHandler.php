<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\Queries\ListBooksByCollectionQuery;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for ListBooksByCollectionQuery
 *
 * @implements QueryHandler<ListBooksByCollectionQuery, PaginatedResult>
 */
final class ListBooksByCollectionHandler implements QueryHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $bookRepository,
        private readonly CollectionRepositoryInterface $collectionRepository,
    ) {}

    /**
     * Handle the query
     *
     * @param  ListBooksByCollectionQuery  $query
     *
     * @throws EntityNotFoundException
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListBooksByCollectionQuery $query */

        // Validate collection exists
        $collection = $this->collectionRepository->find($query->collectionId);
        if ($collection === null) {
            throw EntityNotFoundException::forEntity('HadithCollection', $query->collectionId);
        }

        $paginator = $this->bookRepository->paginateByCollection(
            $query->collectionId,
            $query->perPage,
            $query->sortBy,
            $query->sortDirection
        );

        $locale = $query->locale ?? 'ar';
        $items = $paginator->getCollection()->map(
            fn ($book) => BookDTO::fromModel($book, $locale)
        );

        return PaginatedResult::fromPaginatorWithItems($paginator, $items->toArray());
    }
}
