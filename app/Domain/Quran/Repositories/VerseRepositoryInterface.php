<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface VerseRepositoryInterface extends RepositoryInterface
{
    /**
     * Get verses by surah
     *
     * @param int $surahId
     * @return Collection
     */
    public function getBySurah(int $surahId): Collection;

    /**
     * Get verse by surah number and verse number
     *
     * @param int $surahNumber
     * @param int $verseNumber
     * @return \App\Domain\Quran\Models\Verse|null
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?\App\Domain\Quran\Models\Verse;

    /**
     * Get verses by Juz
     *
     * @param int $juz
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator;

    /**
     * Get verses by page
     *
     * @param int $page
     * @return Collection
     */
    public function getByPage(int $page): Collection;

    /**
     * Get all Sajda verses
     *
     * @return Collection
     */
    public function getSajdaVerses(): Collection;

    /**
     * Search in Arabic text
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator;

    /**
     * Search in translations
     *
     * @param string $query
     * @param string $language
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator;

    /**
     * Get random verse
     *
     * @return \App\Domain\Quran\Models\Verse|null
     */
    public function getRandomVerse(): ?\App\Domain\Quran\Models\Verse;
}

