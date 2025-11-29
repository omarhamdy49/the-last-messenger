<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\GetRandomVerseQuery;
use App\Domain\Quran\Exceptions\VerseNotFoundException;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;

/**
 * Handler for getting a random verse
 */
final readonly class GetRandomVerseHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(GetRandomVerseQuery $query): VerseDTO
    {
        $verse = $this->verseRepository->getRandomVerse();

        if ($verse === null) {
            throw new VerseNotFoundException('No verses found in database.');
        }

        if ($query->withTranslations) {
            $verse->load('translations');
        }

        $verse->load('surah');

        return VerseDTO::fromModel($verse, $query->locale);
    }
}
