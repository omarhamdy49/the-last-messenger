<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Verse;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class VerseRepository extends BaseRepository implements VerseRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour - Quran data rarely changes)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Verse;
    }

    /**
     * Get verses by surah
     */
    public function getBySurah(int $surahId): Collection
    {
        return Cache::remember(
            "quran_verses_surah_{$surahId}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('surah_id', $surahId)
                ->orderBy('verse_number')
                ->get()
        );
    }

    /**
     * Get verse by surah number and verse number
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?Verse
    {
        return Cache::remember(
            "quran_verse_{$surahNumber}_{$verseNumber}",
            self::CACHE_TTL,
            fn () => $this->model
                ->whereHas('surah', function ($query) use ($surahNumber) {
                    $query->where('number', $surahNumber);
                })
                ->where('verse_number', $verseNumber)
                ->with(['surah', 'tafseer'])
                ->first()
        );
    }

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->byJuz($juz)
            ->with('surah')
            ->orderBy('verse_key')
            ->paginate($perPage);
    }

    /**
     * Get verses by page
     */
    public function getByPage(int $page): Collection
    {
        return Cache::remember(
            "quran_verses_page_{$page}",
            self::CACHE_TTL,
            fn () => $this->model
                ->byPage($page)
                ->with('surah')
                ->orderBy('verse_key')
                ->get()
        );
    }

    /**
     * Get all Sajda verses
     */
    public function getSajdaVerses(): Collection
    {
        return Cache::remember(
            'quran_sajda_verses',
            self::CACHE_TTL,
            fn () => $this->model
                ->sajda()
                ->with('surah')
                ->orderBy('verse_key')
                ->get()
        );
    }

    /**
     * Search in Arabic text
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->searchArabic($query)
            ->with('surah')
            ->orderBy('verse_key')
            ->paginate($perPage);
    }

    /**
     * Search in translations
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator
    {
        return $this->model
            ->searchTranslation($query, $language)
            ->with('surah')
            ->orderBy('verse_key')
            ->paginate($perPage);
    }

    /**
     * Get random verse
     */
    public function getRandomVerse(): ?Verse
    {
        // Get random verse key (1-6236)
        $randomKey = rand(1, 6236);

        return $this->model
            ->where('verse_key', $randomKey)
            ->with('surah')
            ->first();
    }
}
