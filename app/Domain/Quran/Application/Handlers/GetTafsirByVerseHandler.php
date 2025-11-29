<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\TafsirDTO;
use App\Domain\Quran\Application\Queries\GetTafsirByVerseQuery;
use App\Domain\Quran\Repositories\TafseerRepositoryInterface;
use Illuminate\Support\Collection;

/**
 * Handler for getting tafsir for a verse
 */
final readonly class GetTafsirByVerseHandler
{
    public function __construct(
        private TafseerRepositoryInterface $tafseerRepository,
    ) {}

    /**
     * @return Collection<int, TafsirDTO>
     */
    public function handle(GetTafsirByVerseQuery $query): Collection
    {
        $verse = app(\App\Domain\Quran\Repositories\VerseRepositoryInterface::class)->find($query->verseId);

        if ($verse === null) {
            return collect();
        }

        $verseKey = "{$verse->surah_id}:{$verse->verse_number}";
        $tafsirs = $this->tafseerRepository->getByVerseReference($verseKey);

        return $tafsirs->map(
            fn ($tafsir) => TafsirDTO::fromModel($tafsir, $query->locale)
        );
    }
}
