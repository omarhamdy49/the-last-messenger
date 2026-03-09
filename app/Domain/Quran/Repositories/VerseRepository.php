<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Models\Verse;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Quran\Models\VerseTranslation;
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
     * Columns to select for verse queries (no select * anywhere)
     */
    private const VERSE_COLUMNS = [
        'id',
        'surah_id',
        'verse_number',
        'verse_key',
        'juz_number',
        'hizb_number',
        'rub_el_hizb_number',
        'ruku_number',
        'manzil_number',
        'sajdah_number',
        'page_number',
    ];

    /**
     * Constrained surah eager load columns
     */
    private const SURAH_EAGER = 'surah:id,revelation_order,revelation_place,name_ar,name_en,transliteration,verses_count';

    /**
     * Constrained translations eager load columns
     */
    private const TRANSLATIONS_EAGER = 'translations:id,verse_id,lang,text,text_notashkeel';

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
            QuranCacheKeys::versesBySurah($surahId),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->where('surah_id', $surahId)
                ->with([self::TRANSLATIONS_EAGER])
                ->orderBy('verse_number')
                ->get()
        );
    }

    /**
     * Get verse by surah number and verse number.
     * surahNumber maps to quran_surahs.revelation_order.
     */
    public function getBySurahAndVerse(int $surahNumber, int $verseNumber): ?Verse
    {
        return Cache::remember(
            QuranCacheKeys::verseBySurahAndNumber($surahNumber, $verseNumber),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->whereHas('surah', function ($query) use ($surahNumber) {
                    $query->where('revelation_order', $surahNumber);
                })
                ->where('verse_number', $verseNumber)
                ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER])
                ->first()
        );
    }

    /**
     * Get verses by Juz
     */
    public function getByJuz(int $juz, int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->where('juz_number', $juz)
            ->with([self::SURAH_EAGER])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Get verses by page
     */
    public function getByPage(int $page): Collection
    {
        return Cache::remember(
            QuranCacheKeys::versesByPage($page),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->where('page_number', $page)
                ->with([self::SURAH_EAGER])
                ->orderBy('surah_id')
                ->orderBy('verse_number')
                ->get()
        );
    }

    /**
     * Get all Sajda verses — sajdah_number IS NOT NULL indicates a sajda verse
     */
    public function getSajdaVerses(): Collection
    {
        return Cache::remember(
            QuranCacheKeys::sajdaVerses(),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::VERSE_COLUMNS)
                ->whereNotNull('sajdah_number')
                ->with([self::SURAH_EAGER])
                ->orderBy('sajdah_number')
                ->get()
        );
    }

    /**
     * Search in Arabic text via quran_verse_translations (lang='ar')
     */
    public function searchArabic(string $query, int $perPage = 20): LengthAwarePaginator
    {
        $matchingVerseIds = VerseTranslation::query()
            ->select('verse_id')
            ->where('lang', 'ar')
            ->where(function ($q) use ($query) {
                $q->where('text', 'LIKE', "%{$query}%")
                    ->orWhere('text_notashkeel', 'LIKE', "%{$query}%");
            })
            ->pluck('verse_id');

        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->whereIn('id', $matchingVerseIds)
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Search in translations for a given language
     */
    public function searchTranslation(string $query, string $language, int $perPage = 20): LengthAwarePaginator
    {
        $matchingVerseIds = VerseTranslation::query()
            ->select('verse_id')
            ->where('lang', $language)
            ->where(function ($q) use ($query) {
                $q->where('text', 'LIKE', "%{$query}%")
                    ->orWhere('text_notashkeel', 'LIKE', "%{$query}%");
            })
            ->pluck('verse_id');

        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->whereIn('id', $matchingVerseIds)
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER])
            ->orderBy('surah_id')
            ->orderBy('verse_number')
            ->paginate($perPage);
    }

    /**
     * Get random verse — picks a random record using inRandomOrder
     */
    public function getRandomVerse(): ?Verse
    {
        return $this->model
            ->select(self::VERSE_COLUMNS)
            ->with([self::SURAH_EAGER, self::TRANSLATIONS_EAGER])
            ->inRandomOrder()
            ->first();
    }

    /**
     * Get paginated verses with filters
     *
     * @param int $perPage
     * @param string $search
     * @param string $sortBy
     * @param array<string, mixed> $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'verse_key',
        array $filters = []
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model
            ->select(self::VERSE_COLUMNS)
            ->with([self::SURAH_EAGER]);

        // Search via translations table (language defaults to Arabic)
        if ($search !== '') {
            $searchLang = $filters['search_lang'] ?? 'ar';
            $matchingIds = VerseTranslation::query()
                ->select('verse_id')
                ->where('lang', $searchLang)
                ->where(function ($q) use ($search) {
                    $q->where('text', 'LIKE', "%{$search}%")
                        ->orWhere('text_notashkeel', 'LIKE', "%{$search}%");
                })
                ->pluck('verse_id');

            $query->whereIn('id', $matchingIds);
        }

        // Apply filters
        if (!empty($filters['surah_id'])) {
            $query->where('surah_id', $filters['surah_id']);
        }

        if (!empty($filters['juz_number'])) {
            $query->where('juz_number', $filters['juz_number']);
        }

        if (!empty($filters['page_number'])) {
            $query->where('page_number', $filters['page_number']);
        }

        if (isset($filters['is_sajda']) && $filters['is_sajda'] !== '') {
            $isSajda = filter_var($filters['is_sajda'], FILTER_VALIDATE_BOOLEAN);
            if ($isSajda) {
                $query->whereNotNull('sajdah_number');
            } else {
                $query->whereNull('sajdah_number');
            }
        }

        // Apply sorting
        $allowedSorts = ['verse_key', 'verse_number', 'juz_number', 'page_number', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'verse_key';
        $query->orderBy($sortBy);

        return $query->paginate($perPage);
    }

    /**
     * Bulk delete verses
     *
     * @param array<int> $ids
     * @return int
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn('id', $ids)->delete();
    }

    /**
     * Search verses with filters
     *
     * @param string $query
     * @param int $perPage
     * @param int|null $surahId
     * @param string $language
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function search(
        string $query,
        int $perPage = 15,
        ?int $surahId = null,
        string $language = 'ar'
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $matchingIds = VerseTranslation::query()
            ->select('verse_id')
            ->where('lang', $language)
            ->where(function ($q) use ($query) {
                $q->where('text', 'LIKE', "%{$query}%")
                    ->orWhere('text_notashkeel', 'LIKE', "%{$query}%");
            })
            ->pluck('verse_id');

        $q = $this->model
            ->select(self::VERSE_COLUMNS)
            ->with([self::SURAH_EAGER])
            ->whereIn('id', $matchingIds);

        if ($surahId !== null) {
            $q->where('surah_id', $surahId);
        }

        return $q->orderBy('surah_id')->orderBy('verse_number')->paginate($perPage);
    }

    /**
     * Count Sajda verses — sajdah_number IS NOT NULL
     *
     * @return int
     */
    public function countSajdaVerses(): int
    {
        return Cache::remember(
            QuranCacheKeys::sajdaVersesCount(),
            self::CACHE_TTL,
            fn () => $this->model->whereNotNull('sajdah_number')->count()
        );
    }
}
