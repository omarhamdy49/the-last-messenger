<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\Queries\GetCollectionByIdQuery;
use App\Domain\Hadith\Exceptions\CollectionNotFoundException;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetCollectionByIdQuery
 *
 * @implements QueryHandler<GetCollectionByIdQuery, CollectionDTO>
 */
final class GetCollectionByIdHandler implements QueryHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param GetCollectionByIdQuery $query
     * @return CollectionDTO
     * @throws CollectionNotFoundException
     */
    public function handle(Query $query): CollectionDTO
    {
        /** @var GetCollectionByIdQuery $query */
        $collection = $query->withBooks
            ? $this->repository->getWithBooks($query->collectionId)
            : $this->repository->find($query->collectionId);

        if ($collection === null) {
            throw CollectionNotFoundException::withCollectionId($query->collectionId);
        }

        return CollectionDTO::fromModel($collection, $query->locale ?? 'ar');
    }
}
