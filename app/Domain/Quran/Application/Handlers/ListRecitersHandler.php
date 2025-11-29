<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\Queries\ListRecitersQuery;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Handler for listing reciters
 */
final readonly class ListRecitersHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    public function handle(ListRecitersQuery $query): LengthAwarePaginator|Collection
    {
        if ($query->featuredOnly) {
            $reciters = $this->reciterRepository->getFeatured($query->activeOnly);

            return $reciters->map(
                fn ($reciter) => ReciterDTO::fromModel($reciter, $query->locale)
            );
        }

        if ($query->country !== null) {
            $reciters = $this->reciterRepository->getByCountry($query->country, $query->activeOnly);

            return $reciters->map(
                fn ($reciter) => ReciterDTO::fromModel($reciter, $query->locale)
            );
        }

        if ($query->style !== null) {
            $reciters = $this->reciterRepository->getByStyle($query->style, $query->activeOnly);

            return $reciters->map(
                fn ($reciter) => ReciterDTO::fromModel($reciter, $query->locale)
            );
        }

        $results = $this->reciterRepository->paginate($query->perPage, $query->activeOnly);

        $results->getCollection()->transform(
            fn ($reciter) => ReciterDTO::fromModel($reciter, $query->locale)
        );

        return $results;
    }
}
