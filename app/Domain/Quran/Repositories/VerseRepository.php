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
        $query = $this->model->query()->with('surah');

        // Apply search across text and translations
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('text_simple', 'LIKE', "%{$search}%")
                    ->orWhere('text_uthmani', 'LIKE', "%{$search}%")
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.ar')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.fr')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.tr')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.ur')) LIKE ?", ["%{$search}%"]);
            });
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
            $query->where('is_sajda', filter_var($filters['is_sajda'], FILTER_VALIDATE_BOOLEAN));
        }

        // Apply sorting
        $allowedSorts = ['verse_key', 'verse_number', 'juz_number', 'page_number', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'verse_key';
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
        string $language = 'en'
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $q = $this->model->query()->with('surah');

        // Search in Arabic text and specific language translation
        $q->where(function ($subQuery) use ($query, $language) {
            $subQuery->where('text_simple', 'LIKE', "%{$query}%")
                ->orWhere('text_uthmani', 'LIKE', "%{$query}%")
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(translations, '$.{$language}')) LIKE ?", ["%{$query}%"]);
        });

        // Filter by surah if provided
        if ($surahId !== null) {
            $q->where('surah_id', $surahId);
        }

        return $q->orderBy('verse_key')->paginate($perPage);
    }

    /**
     * Count Sajda verses
     *
     * @return int
     */
    public function countSajdaVerses(): int
    {
        return Cache::remember(
            'quran_sajda_count',
            self::CACHE_TTL,
            fn () => $this->model->where('is_sajda', true)->count()
        );
    }
}
