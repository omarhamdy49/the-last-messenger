<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\ReciterDTO;
use App\Domain\Quran\Application\Queries\SearchRecitersQuery;
use App\Domain\Quran\Repositories\ReciterRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for searching reciters
 */
final readonly class SearchRecitersHandler
{
    public function __construct(
        private ReciterRepositoryInterface $reciterRepository,
    ) {}

    /**
     * @return Collection<int, ReciterDTO>
     */
    public function handle(SearchRecitersQuery $query): Collection
    {
        $reciters = $this->reciterRepository->search($query->query, $query->activeOnly);

        return $reciters->map(
            fn ($reciter) => ReciterDTO::fromModel($reciter, $query->locale)
        );
    }
}
