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
     */
    public function getBySurah(int $surahId): Collection;

    /**
     * Get verse by surah number and verse number
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?\App\Domain\Quran\Models\Verse;

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator;

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
    public function getRandomVerse(): ?\App\Domain\Quran\Models\Verse;
}
