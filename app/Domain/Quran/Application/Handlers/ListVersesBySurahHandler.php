<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\ListVersesBySurahQuery;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for listing verses by Surah
 */
final readonly class ListVersesBySurahHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    /**
     * @return Collection<int, VerseDTO>
     */
    public function handle(ListVersesBySurahQuery $query): Collection
    {
        $verses = $this->verseRepository->getBySurah($query->surahId);

        if ($query->withTranslations) {
            $verses->load(Verse::readerTranslationsEagerLoad($query->locale));
        }

        return $verses->map(
            fn ($verse) => VerseDTO::fromModel($verse, $query->locale)
        );
    }
}
