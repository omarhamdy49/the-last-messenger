<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\SurahDTO;
use App\Domain\Quran\Application\Queries\GetSurahByIdQuery;
use App\Domain\Quran\Exceptions\SurahNotFoundException;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;

/**
 * Handler for getting a Surah by ID
 */
final readonly class GetSurahByIdHandler
{
    public function __construct(
        private SurahRepositoryInterface $surahRepository,
    ) {}

    public function handle(GetSurahByIdQuery $query): SurahDTO
    {
        $relations = [];

        if ($query->withInfo) {
            $relations[] = 'info';
        }

        if ($query->withVerses) {
            $relations[] = 'verses';
            $relations[] = 'verses.translations';
        }

        $surah = $this->surahRepository->find($query->id, ['*'], $relations);

        if ($surah === null) {
            throw SurahNotFoundException::withId($query->id);
        }

        return SurahDTO::fromModel($surah, $query->locale);
    }
}
