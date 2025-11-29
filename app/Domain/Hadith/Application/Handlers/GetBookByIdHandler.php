<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\Queries\GetBookByIdQuery;
use App\Domain\Hadith\Repositories\BookRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;
use App\Domain\Shared\Exceptions\EntityNotFoundException;

/**
 * Handler for GetBookByIdQuery
 *
 * @implements QueryHandler<GetBookByIdQuery, BookDTO>
 */
final class GetBookByIdHandler implements QueryHandler
{
    public function __construct(
        private readonly BookRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param GetBookByIdQuery $query
     * @return BookDTO
     * @throws EntityNotFoundException
     */
    public function handle(Query $query): BookDTO
    {
        /** @var GetBookByIdQuery $query */
        $book = $this->repository->find($query->bookId);

        if ($book === null) {
            throw EntityNotFoundException::forEntity('HadithBook', $query->bookId);
        }

        $relations = [];
        if ($query->withChapters) {
            $relations[] = 'chapters';
        }
        if ($query->withCollection) {
            $relations[] = 'collection';
        }

        if (!empty($relations)) {
            $book->load($relations);
        }

        return BookDTO::fromModel($book, $query->locale ?? 'ar');
    }
}
