<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\DTOs\ChapterContextDTO;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\Queries\GetChapterContextQuery;
use App\Domain\Hadith\Exceptions\CollectionNotFoundException;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for GetChapterContextQuery
 *
 * Resolves the public chapter (kitab) page header: collection by slug,
 * book by number within that collection, and adjacent books for navigation.
 *
 * @implements QueryHandler<GetChapterContextQuery, ChapterContextDTO>
 */
final class GetChapterContextHandler implements QueryHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $collectionRepository,
        private readonly BookRepositoryInterface $bookRepository,
    ) {}

    /**
     * Handle the query
     *
     * @param  GetChapterContextQuery  $query
     *
     * @throws CollectionNotFoundException
     * @throws EntityNotFoundException
     */
    public function handle(Query $query): ChapterContextDTO
    {
        /** @var GetChapterContextQuery $query */
        $locale = $query->locale ?? 'ar';

        $collection = $this->collectionRepository->getBySlug($query->collectionSlug);
        if ($collection === null) {
            throw CollectionNotFoundException::withCollectionSlug($query->collectionSlug);
        }

        $book = $this->bookRepository->getPublicByCollectionAndNumber($collection->id, $query->bookNumber);
        if ($book === null) {
            throw EntityNotFoundException::withCriteria('HadithBook', [
                'collection_id' => $collection->id,
                'book_number' => $query->bookNumber,
            ]);
        }

        // Avoid the per-model books()->count() fallback inside CollectionDTO.
        $collection->loadCount('books');

        $adjacent = $this->bookRepository->getAdjacentByNumber($collection->id, $query->bookNumber);

        // book_number is stored as a string column — normalize to int for the DTO.
        $previousNumber = $adjacent['previous']?->book_number;
        $nextNumber = $adjacent['next']?->book_number;

        return new ChapterContextDTO(
            collection: CollectionDTO::fromModel($collection, $locale),
            book: BookDTO::fromModel($book, $locale),
            previousNumber: $previousNumber !== null ? (int) $previousNumber : null,
            previousTitleAr: $adjacent['previous']?->getTitleInLocale('ar'),
            previousTitleEn: $adjacent['previous']?->getTitleInLocale('en'),
            nextNumber: $nextNumber !== null ? (int) $nextNumber : null,
            nextTitleAr: $adjacent['next']?->getTitleInLocale('ar'),
            nextTitleEn: $adjacent['next']?->getTitleInLocale('en'),
        );
    }
}
