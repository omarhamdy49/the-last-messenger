<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\ListVersesByJuzQuery;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for listing verses by Juz
 */
final readonly class ListVersesByJuzHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(ListVersesByJuzQuery $query): LengthAwarePaginator
    {
        $results = $this->verseRepository->getByJuz($query->juz, $query->perPage);

        if ($query->withTranslations) {
            $results->getCollection()->load(Verse::readerTranslationsEagerLoad($query->locale));
        }

        $results->getCollection()->transform(
            fn ($verse) => VerseDTO::fromModel($verse, $query->locale)
        );

        return $results;
    }
}
