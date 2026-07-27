<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SurahRepository extends BaseRepository implements SurahRepositoryInterface
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
        return new Surah;
    }

    /**
     * Columns to select for surah list queries
     */
    private const SURAH_COLUMNS = [
        'id',
        'revelation_place',
        'revelation_order',
        'bismillah_pre',
        'name_ar',
        'name_en',
        'transliteration',
        'verses_count',
        'pages',
    ];

    /**
     * Get all surahs (cached)
     */
    public function all(array $columns = [], array $relations = []): Collection
    {
        $selectColumns = $columns !== [] ? $columns : self::SURAH_COLUMNS;

        return Cache::remember(
            QuranCacheKeys::surahAll(),
            self::CACHE_TTL,
            fn () => $this->model
                ->select($selectColumns)
                ->with($relations)
                ->orderBy('revelation_order')
                ->get()
        );
    }

    /**
     * Get surah by number (1-114) — number maps to revelation_order
     */
    public function getByNumber(int $number): ?Surah
    {
        return Cache::remember(
            QuranCacheKeys::surahByNumber($number),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::SURAH_COLUMNS)
                ->where('revelation_order', $number)
                ->first()
        );
    }

    /**
     * Get surahs by revelation place
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection
    {
        $placeValue = $place instanceof RevelationPlace ? $place->value : $place;

        return Cache::remember(
            QuranCacheKeys::surahsByRevelationPlace($placeValue),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::SURAH_COLUMNS)
                ->where('revelation_place', $placeValue)
                ->orderBy('revelation_order')
                ->get()
        );
    }

    /**
     * Get surahs by Juz — not directly queryable on surah level;
     * returns all surahs whose pages overlap the given juz.
     * Since there is no juz column on surahs, this returns all surahs
     * ordered by revelation_order as a safe fallback.
     */
    public function getByJuz(int $juz): Collection
    {
        return Cache::remember(
            QuranCacheKeys::surahsByJuz($juz),
            self::CACHE_TTL,
            fn () => $this->model
                ->select(self::SURAH_COLUMNS)
                ->orderBy('revelation_order')
                ->get()
        );
    }

    /**
     * Get paginated surahs with filters
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'revelation_order',
        array $filters = []
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model->select(self::SURAH_COLUMNS);

        // Apply search across name_ar, name_en, and transliteration
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('transliteration', 'LIKE', "%{$search}%")
                    ->orWhere('revelation_order', 'LIKE', "%{$search}%");
            });
        }

        // Apply filters
        if (! empty($filters['revelation_place'])) {
            $query->where('revelation_place', $filters['revelation_place']);
        }

        // Apply sorting
        $allowedSorts = ['revelation_order', 'verses_count', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'revelation_order';
        $query->orderBy($sortBy);

        return $query->paginate($perPage);
    }

    /**
     * Count surahs by revelation place
     */
    public function countByRevelation(string $place): int
    {
        return Cache::remember(
            QuranCacheKeys::surahCountByRevelation($place),
            self::CACHE_TTL,
            fn () => $this->model->where('revelation_place', $place)->count()
        );
    }

    /**
     * Get total verses count across all surahs
     */
    public function getTotalVersesCount(): int
    {
        return Cache::remember(
            QuranCacheKeys::surahTotalVerses(),
            self::CACHE_TTL,
            fn () => (int) $this->model->sum('verses_count')
        );
    }

    /**
     * Search surahs by name_ar, name_en, and transliteration
     */
    public function search(string $query, int $perPage = 15, ?string $revelationPlace = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $q = $this->model->select(self::SURAH_COLUMNS);

        $q->where(function ($subQuery) use ($query) {
            $subQuery->where('name_ar', 'LIKE', "%{$query}%")
                ->orWhere('name_en', 'LIKE', "%{$query}%")
                ->orWhere('transliteration', 'LIKE', "%{$query}%")
                ->orWhere('revelation_order', 'LIKE', "%{$query}%");
        });

        if ($revelationPlace !== null) {
            $q->where('revelation_place', $revelationPlace);
        }

        return $q->orderBy('revelation_order')->paginate($perPage);
    }

    /**
     * Get average verses per surah
     */
    public function getAverageVersesPerSurah(): float
    {
        return Cache::remember(
            QuranCacheKeys::surahAverageVerses(),
            self::CACHE_TTL,
            // MySQL returns AVG() as a string, which round() rejects under strict_types.
            fn () => round((float) ($this->model->avg('verses_count') ?? 0), 2)
        );
    }
}
