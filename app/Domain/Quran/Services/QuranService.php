<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Repositories\SurahRepositoryInterface;
use App\Domain\Quran\Repositories\VerseRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class QuranService extends BaseService
{
    /**
     * QuranService constructor
     */
    public function __construct(
        private readonly SurahRepositoryInterface $surahRepository,
        private readonly VerseRepositoryInterface $verseRepository
    ) {
        // Note: QuranService uses multiple repositories, not extending BaseService fully
        // The surahRepository will be used as the primary repository for paginate() calls
        $this->repository = $surahRepository;
    }

    // ===================== SURAH METHODS =====================

    /**
     * Get all surahs
     */
    public function getAllSurahs(): Collection
    {
        return $this->surahRepository->all();
    }

    /**
     * Get surah by number
     *
     * @throws ResourceNotFoundException
     */
    public function getSurahByNumber(int $number): Surah
    {
        if ($number < 1 || $number > 114) {
            throw new ResourceNotFoundException("Invalid surah number: {$number}. Must be between 1 and 114.");
        }

        $surah = $this->surahRepository->getByNumber($number);

        if (! $surah) {
            throw new ResourceNotFoundException("Surah not found: {$number}");
        }

        return $surah;
    }

    /**
     * Get surahs by revelation place
     */
    public function getSurahsByRevelationPlace(RevelationPlace|string $place): Collection
    {
        return $this->surahRepository->getByRevelationPlace($place);
    }

    /**
     * Get surahs by Juz
     */
    public function getSurahsByJuz(int $juz): Collection
    {
        if ($juz < 1 || $juz > 30) {
            throw new ResourceNotFoundException("Invalid juz number: {$juz}. Must be between 1 and 30.");
        }

        return $this->surahRepository->getByJuz($juz);
    }

    // ===================== VERSE METHODS =====================

    /**
     * Get verses by surah number
     *
     * @throws ResourceNotFoundException
     */
    public function getVersesBySurahNumber(int $surahNumber): Collection
    {
        $surah = $this->getSurahByNumber($surahNumber);

        return $this->verseRepository->getBySurah($surah->id);
    }

    /**
     * Get single verse by surah and verse number
     *
     * @throws ResourceNotFoundException
     */
    public function getVerse(int $surahNumber, int $verseNumber): Verse
    {
        $verse = $this->verseRepository->getBySurahAndVerse($surahNumber, $verseNumber);

        if (! $verse) {
            throw new ResourceNotFoundException("Verse not found: {$surahNumber}:{$verseNumber}");
        }

        return $verse;
    }

    /**
     * Get verses by Juz
     */
    public function getVersesByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        if ($juz < 1 || $juz > 30) {
            throw new ResourceNotFoundException("Invalid juz number: {$juz}. Must be between 1 and 30.");
        }

        return $this->verseRepository->getByJuz($juz, $perPage);
    }

    /**
     * Get verses by page number
     */
    public function getVersesByPage(int $page): Collection
    {
        if ($page < 1 || $page > 604) {
            throw new ResourceNotFoundException("Invalid page number: {$page}. Must be between 1 and 604.");
        }

        return $this->verseRepository->getByPage($page);
    }

    /**
     * Get all Sajda verses
     */
    public function getSajdaVerses(): Collection
    {
        return $this->verseRepository->getSajdaVerses();
    }

    /**
     * Get random verse (Verse of the Day)
     */
    public function getRandomVerse(): Verse
    {
        return $this->verseRepository->getRandomVerse();
    }

    // ===================== SEARCH METHODS =====================

    /**
     * Search in Arabic text
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->verseRepository->searchArabic($query, $perPage);
    }

    /**
     * Search in translations
     */
    public function searchTranslation(string $query, string $language = 'en', int $perPage = 20): LengthAwarePaginator
    {
        return $this->verseRepository->searchTranslation($query, $language, $perPage);
    }
}
