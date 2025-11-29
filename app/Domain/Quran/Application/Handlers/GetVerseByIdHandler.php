<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\GetVerseByIdQuery;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for getting a verse by ID
 */
final readonly class GetVerseByIdHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(GetVerseByIdQuery $query): VerseDTO
    {
        $relations = ['surah'];

        if ($query->withTranslations) {
            $relations[] = 'translations';
        }

        if ($query->withTafsir) {
            $relations[] = 'tafsir';
        }

        $verse = $this->verseRepository->find($query->id, ['*'], $relations);

        if ($verse === null) {
            throw VerseNotFoundException::withId($query->id);
        }

        return VerseDTO::fromModel($verse, $query->locale);
    }
}
