<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\ListVersesByHizbQuery;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for listing verses by Hizb
 */
final readonly class ListVersesByHizbHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(ListVersesByHizbQuery $query): LengthAwarePaginator
    {
        $results = $this->verseRepository->getByHizb($query->hizb, $query->perPage);

        if ($query->withTranslations) {
            $results->getCollection()->load(Verse::readerTranslationsEagerLoad($query->locale));
        }

        $results->getCollection()->transform(
            fn ($verse) => VerseDTO::fromModel($verse, $query->locale)
        );

        return $results;
    }
}
