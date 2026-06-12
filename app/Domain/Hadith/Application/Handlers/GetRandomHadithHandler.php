<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Application\Queries\GetRandomHadithQuery;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetRandomHadithQuery
 *
 * @implements QueryHandler<GetRandomHadithQuery, HadithDTO>
 */
final class GetRandomHadithHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository
    ) {}

    /**
     * Handle the query
     *
     * @param  GetRandomHadithQuery  $query
     *
     * @throws HadithNotFoundException
     */
    public function handle(Query $query): HadithDTO
    {
        /** @var GetRandomHadithQuery $query */
        $hadith = $this->repository->getRandom($query->gradingStatus);

        if ($hadith === null) {
            throw new HadithNotFoundException('No hadith found matching the criteria');
        }

        if ($query->withTranslations) {
            $hadith->load(['translations', 'book.collection', 'themes']);
        }

        return HadithDTO::fromModel($hadith, $query->locale ?? 'ar');
    }
}
