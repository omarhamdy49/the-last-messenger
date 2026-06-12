<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDetailDTO;
use App\Domain\Hadith\Application\Queries\GetHadithDetailQuery;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetHadithDetailQuery
 *
 * Loads the full public detail of a hadith in one eager-loaded query path
 * plus a single aggregate for the parallel-narrations count.
 *
 * @implements QueryHandler<GetHadithDetailQuery, HadithDetailDTO>
 */
final class GetHadithDetailHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository,
    ) {}

    /**
     * Handle the query
     *
     * @param  GetHadithDetailQuery  $query
     *
     * @throws HadithNotFoundException
     */
    public function handle(Query $query): HadithDetailDTO
    {
        /** @var GetHadithDetailQuery $query */
        $hadith = $this->repository->getWithPublicDetail($query->hadithId);

        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($query->hadithId);
        }

        $parallelsCount = $hadith->text_hash !== null
            ? $this->repository->countByTextHash($hadith->text_hash, $hadith->id)
            : 0;

        return HadithDetailDTO::fromModel($hadith, $query->locale ?? 'ar', $parallelsCount);
    }
}
