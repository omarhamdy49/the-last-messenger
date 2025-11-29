<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Application\Queries\ListTafsirBySurahQuery;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for listing tafsir by Surah
 */
final readonly class ListTafsirBySurahHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
    ) {}

    public function handle(ListTafsirBySurahQuery $query): LengthAwarePaginator
    {
        $results = $this->tafseerRepository->getBySurah($query->surahId, $query->perPage);

        $results->getCollection()->transform(
            fn ($tafsir) => TafsirDTO::fromModel($tafsir, $query->locale)
        );

        return $results;
    }
}
