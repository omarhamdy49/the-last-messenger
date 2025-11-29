<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\ListHadithsByBookQuery;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for ListHadithsByBookQuery
 *
 * @implements QueryHandler<ListHadithsByBookQuery, PaginatedResult>
 */
final class ListHadithsByBookHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * Handle the query
     *
     * @param ListHadithsByBookQuery $query
     * @return PaginatedResult
     * @throws EntityNotFoundException
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListHadithsByBookQuery $query */

        // Validate book exists
        $book = $this->bookRepository->find($query->bookId);
        if ($book === null) {
            throw EntityNotFoundException::forEntity('HadithBook', $query->bookId);
        }

        $filters = [
            'book_id' => $query->bookId,
        ];

        if ($query->gradingStatus !== null) {
            $filters['grading_status'] = $query->gradingStatus;
        }

        $paginator = $this->hadithRepository->paginateWithFilters(
            perPage: $query->perPage,
            sortBy: $query->sortBy,
            filters: $filters
        );

        $locale = $query->locale ?? 'ar';
        $items = $paginator->getCollection()->map(
            fn($hadith) => HadithDTO::fromModel($hadith, $locale)
        );

        return PaginatedResult::fromPaginator($paginator, $items->toArray());
    }
}
