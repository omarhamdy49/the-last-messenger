<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\ListChapterHadithsQuery;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for ListChapterHadithsQuery
 *
 * Paginates a book's hadiths for the public chapter reader, with
 * search-within-chapter and grading filter applied at the database level.
 *
 * @implements QueryHandler<ListChapterHadithsQuery, PaginatedResult>
 */
final class ListChapterHadithsHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $hadithRepository,
    ) {}

    /**
     * Handle the query
     *
     * @param  ListChapterHadithsQuery  $query
     */
    public function handle(Query $query): PaginatedResult
    {
        /** @var ListChapterHadithsQuery $query */
        $locale = $query->locale ?? 'ar';

        $paginator = $this->hadithRepository->paginatePublicByBook(
            bookId: $query->bookId,
            perPage: $query->perPage,
            page: $query->page,
            gradingStatus: $query->gradingStatus,
            search: $query->search,
            locale: $locale,
        );

        $items = $paginator->getCollection()->map(
            fn ($hadith) => HadithDTO::fromModel($hadith, $locale)
        );

        return PaginatedResult::fromPaginatorWithItems($paginator, $items->toArray());
    }
}
