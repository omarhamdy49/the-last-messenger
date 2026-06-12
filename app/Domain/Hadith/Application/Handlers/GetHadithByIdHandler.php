<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\GetHadithByIdQuery;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetHadithByIdQuery
 *
 * @implements QueryHandler<GetHadithByIdQuery, HadithDTO>
 */
final class GetHadithByIdHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param  GetHadithByIdQuery  $query
     *
     * @throws HadithNotFoundException
     */
    public function handle(Query $query): HadithDTO
    {
        /** @var GetHadithByIdQuery $query */
        $hadith = $query->withRelations
            ? $this->repository->getWithFullRelations($query->hadithId)
            : $this->repository->find($query->hadithId);

        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($query->hadithId);
        }

        return HadithDTO::fromModel($hadith, $query->locale ?? 'ar');
    }
}
