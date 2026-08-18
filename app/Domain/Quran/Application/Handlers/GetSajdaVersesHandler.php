<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\GetSajdaVersesQuery;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for getting Sajda (prostration) verses
 */
final readonly class GetSajdaVersesHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    /**
     * @return Collection<int, VerseDTO>
     */
    public function handle(GetSajdaVersesQuery $query): Collection
    {
        $verses = $this->verseRepository->getSajdaVerses();

        if ($query->withTranslations) {
            $verses->load(Verse::readerTranslationsEagerLoad($query->locale));
        }

        $verses->load('surah');

        return $verses->map(
            fn ($verse) => VerseDTO::fromModel($verse, $query->locale)
        );
    }
}
