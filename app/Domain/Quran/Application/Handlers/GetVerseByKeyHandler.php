<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\GetVerseByKeyQuery;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for getting a verse by surah:verse key
 */
final readonly class GetVerseByKeyHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(GetVerseByKeyQuery $query): VerseDTO
    {
        $verse = $this->verseRepository->getBySurahAndVerse(
            $query->surahNumber,
            $query->verseNumber
        );

        if ($verse === null) {
            throw VerseNotFoundException::withSurahAndVerse(
                $query->surahNumber,
                $query->verseNumber
            );
        }

        if ($query->withTranslations) {
            $verse->load(Verse::readerTranslationsEagerLoad($query->locale));
        }

        if ($query->withTafsir) {
            $verse->load('tafsir');
        }

        $verse->load('surah');

        return VerseDTO::fromModel($verse, $query->locale);
    }
}
