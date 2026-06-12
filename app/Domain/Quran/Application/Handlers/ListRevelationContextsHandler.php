<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\RevelationContextDTO;
use App\Domain\Quran\Application\Queries\ListRevelationContextsQuery;
use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Repositories\RevelationContextRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for listing revelation contexts (Asbab al-Nuzul)
 */
final readonly class ListRevelationContextsHandler
{
    public function __construct(
        private RevelationContextRepositoryInterface $revelationContextRepository,
    ) {}

    public function handle(ListRevelationContextsQuery $query): LengthAwarePaginator
    {
        $results = $this->revelationContextRepository->paginateWithFilters([
            'period' => $query->period,
            'surah_id' => $query->surahId,
            'search' => $query->search,
            'sort' => $query->sort,
            'active_only' => $query->activeOnly,
        ], $query->perPage);

        // One bulk query for all verse excerpts on the page — never per card.
        $excerpts = [];
        if ($query->withExcerpts) {
            $verseRefs = $results->getCollection()
                ->map(fn (RevelationContext $context) => [
                    'surah_id' => $context->surah_id,
                    'verse_number' => $context->verse_from,
                ])
                ->unique(fn (array $ref) => $ref['surah_id'].':'.$ref['verse_number'])
                ->values()
                ->all();

            $excerpts = $this->revelationContextRepository->getArabicExcerptsForContexts($verseRefs);
        }

        $results->getCollection()->transform(
            fn (RevelationContext $context) => RevelationContextDTO::fromModel(
                $context,
                $query->locale,
                $excerpts[$context->surah_id.':'.$context->verse_from] ?? null,
            )
        );

        return $results;
    }
}
