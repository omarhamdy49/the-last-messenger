<?php

declare(strict_types=1);

namespace App\Domain\Quran\Application\Handlers;

use App\Domain\Quran\Application\DTOs\VerseDTO;
use App\Domain\Quran\Application\Queries\SearchVersesQuery;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Handler for searching verses
 */
final readonly class SearchVersesHandler
{
    public function __construct(
        private VerseRepositoryInterface $verseRepository,
    ) {}

    public function handle(SearchVersesQuery $query): LengthAwarePaginator
    {
        // Search based on type
        if ($query->searchArabic && $query->language === 'ar') {
            $results = $this->verseRepository->searchArabic(
                $query->query,
                $query->perPage
            );
        } elseif ($query->searchTranslation) {
            $results = $this->verseRepository->searchTranslation(
                $query->query,
                $query->language,
                $query->perPage
            );
        } else {
            $results = $this->verseRepository->search(
                $query->query,
                $query->perPage,
                $query->surahId,
                $query->language
            );
        }

        $results->getCollection()->transform(
            fn ($verse) => VerseDTO::fromModel($verse, $query->language)
        );

        return $results;
    }
}
