<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Application\Handlers;

use App\Domain\Hadith\Application\DTOs\HadithDetailDTO;
use App\Domain\Hadith\Application\DTOs\ParallelNarrationsDTO;
use App\Domain\Hadith\Application\Queries\GetParallelNarrationsQuery;
use App\Domain\Hadith\Exceptions\HadithNotFoundException;
use App\Domain\Hadith\Repositories\HadithRepositoryInterface;
use App\Domain\Shared\Application\Queries\Query;
use App\Domain\Shared\Application\Queries\QueryHandler;

/**
 * Handler for GetParallelNarrationsQuery
 *
 * Loads a hadith plus every other hadith sharing its text_hash, each with the
 * full public-detail relations (book, collection, translations, isnad,
 * gradings) eager loaded in a single query path.
 *
 * @implements QueryHandler<GetParallelNarrationsQuery, ParallelNarrationsDTO>
 */
final class GetParallelNarrationsHandler implements QueryHandler
{
    public function __construct(
        private readonly HadithRepositoryInterface $repository,
    ) {}

    /**
     * Handle the query
     *
     * @param  GetParallelNarrationsQuery  $query
     *
     * @throws HadithNotFoundException
     */
    public function handle(Query $query): ParallelNarrationsDTO
    {
        /** @var GetParallelNarrationsQuery $query */
        $locale = $query->locale ?? 'ar';

        $hadith = $this->repository->getWithPublicDetail($query->hadithId);
        if ($hadith === null) {
            throw HadithNotFoundException::withHadithId($query->hadithId);
        }

        $parallels = $hadith->text_hash !== null
            ? $this->repository->getByTextHash($hadith->text_hash, $hadith->id)
            : collect();

        $parallelDtos = $parallels
            ->map(fn ($parallel) => HadithDetailDTO::fromModel($parallel, $locale))
            ->values()
            ->all();

        return new ParallelNarrationsDTO(
            base: HadithDetailDTO::fromModel($hadith, $locale, count($parallelDtos)),
            parallels: $parallelDtos,
        );
    }
}
