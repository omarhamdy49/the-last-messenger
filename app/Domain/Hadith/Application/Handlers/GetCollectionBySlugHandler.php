<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\Queries\GetCollectionBySlugQuery;
use App\Domain\Hadith\Exceptions\CollectionNotFoundException;
use App\Domain\Hadith\Repositories\CollectionRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetCollectionBySlugQuery
 *
 * @implements QueryHandler<GetCollectionBySlugQuery, CollectionDTO>
 */
final class GetCollectionBySlugHandler implements QueryHandler
{
    public function __construct(
        private readonly CollectionRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param  GetCollectionBySlugQuery  $query
     *
     * @throws CollectionNotFoundException
     */
    public function handle(Query $query): CollectionDTO
    {
        /** @var GetCollectionBySlugQuery $query */
        $collection = $this->repository->getBySlug($query->slug);

        if ($collection === null) {
            throw CollectionNotFoundException::withCollectionSlug($query->slug);
        }

        if ($query->withBooks) {
            $collection->load('books');
        }

        return CollectionDTO::fromModel($collection, $query->locale ?? 'ar');
    }
}
