<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\TranslationResource;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Models\Word;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VerseRepositoryInterface extends RepositoryInterface
{
    /**
     * Get verses by surah
     */
    public function getBySurah(int $surahId): Collection;

    /**
     * One surah's verses, paginated, carrying only what a listing row shows.
     *
     * Deliberately narrower than getBySurah(): no translation-resource
     * eager-load and no cache, because the admin surah page renders a page of
     * ten rows at a time and must show a verse the moment it is edited.
     */
    public function paginateBySurah(int $surahId, int $perPage): LengthAwarePaginator;

    /**
     * Active translation editions (resources), excluding the Arabic source text.
     *
     * @return Collection<int, TranslationResource>
     */
    public function getActiveTranslationResources(): Collection;

    /**
     * One translation edition's text for a whole surah, keyed by verse id (cached).
     *
     * @return array<int, string>
     */
    public function getSurahTranslationByResource(int $surahNumber, int $resourceId): array;

    /**
     * Get verse by surah number and verse number
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?Verse;

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get verses by Hizb (1-60), paginated and grouped by surah order.
     */
    public function getByHizb(int $hizb, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get verses by page
     */
    public function getByPage(int $page): Collection;

    /**
     * Get all Sajda verses
     */
    public function getSajdaVerses(): Collection;

    /**
     * Search in Arabic text
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Search in translations
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get random verse
     */
    public function getRandomVerse(): ?Verse;

    /**
     * Get paginated verses with filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'verse_key',
        array $filters = []
    ): LengthAwarePaginator;

    /**
     * Bulk delete verses
     */
    public function bulkDelete(array $ids): int;

    /**
     * Search verses with filters
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?int $surahId = null,
        string $language = 'en'
    ): LengthAwarePaginator;

    /**
     * Count Sajda verses
     */
    public function countSajdaVerses(): int;

    /**
     * Printed-mushaf word layout for a surah, ordered for line-by-line rendering
     * (page → line → position).
     *
     * @return Collection<int, Word>
     */
    public function getMushafWordsBySurah(int $surahNumber): Collection;
}
