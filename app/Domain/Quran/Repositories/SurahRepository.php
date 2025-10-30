<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\RevelationPlace;
use App\Domain\Quran\Models\Surah;
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
     * Get all surahs (cached)
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return Cache::remember(
            'quran_surahs_all',
            self::CACHE_TTL,
            fn () => $this->model
                ->with($relations)
                ->orderBy('number')
                ->get($columns)
        );
    }

    /**
     * Get surah by number (1-114)
     */
    public function getByNumber(int $number): ?Surah
    {
        return Cache::remember(
            "quran_surah_number_{$number}",
            self::CACHE_TTL,
            fn () => $this->model->where('number', $number)->first()
        );
    }

    /**
     * Get surahs by revelation place
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection
    {
        $placeValue = $place instanceof RevelationPlace ? $place->value : $place;

        return Cache::remember(
            "quran_surahs_revelation_{$placeValue}",
            self::CACHE_TTL,
            fn () => $this->model
                ->byRevelationPlace($place)
                ->orderBy('number')
                ->get()
        );
    }

    /**
     * Get surahs by Juz
     */
    public function getByJuz(int $juz): Collection
    {
        return Cache::remember(
            "quran_surahs_juz_{$juz}",
            self::CACHE_TTL,
            fn () => $this->model
                ->byJuz($juz)
                ->orderBy('number')
                ->get()
        );
    }

    /**
     * Get paginated surahs with filters
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
        string $sortBy = 'number',
        array $filters = []
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model->query();

        // Apply search across all language fields
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.tr')) LIKE ?", ["%{$search}%"])
                    ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ur')) LIKE ?", ["%{$search}%"])
                    ->orWhere('number', 'LIKE', "%{$search}%");
            });
        }

        // Apply filters
        if (!empty($filters['revelation_place'])) {
            $query->where('revelation_place', $filters['revelation_place']);
        }

        // Apply sorting
        $allowedSorts = ['number', 'revelation_order', 'verses_count', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts) ? $sortBy : 'number';
        $query->orderBy($sortBy);

        return $query->paginate($perPage);
    }

    /**
     * Count surahs by revelation place
     *
     * @param string $place
     * @return int
     */
    public function countByRevelation(string $place): int
    {
        return Cache::remember(
            "quran_surahs_count_{$place}",
            self::CACHE_TTL,
            fn () => $this->model->where('revelation_place', $place)->count()
        );
    }

    /**
     * Get total verses count across all surahs
     *
     * @return int
     */
    public function getTotalVersesCount(): int
    {
        return Cache::remember(
            'quran_total_verses',
            self::CACHE_TTL,
            fn () => $this->model->sum('verses_count')
        );
    }

    /**
     * Search surahs across all languages
     *
     * @param string $query
     * @param int $perPage
     * @param string|null $revelationPlace
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15, ?string $revelationPlace = null): \Illuminate\Pagination\LengthAwarePaginator
    {
        $q = $this->model->query();

        // Search across all language fields
        $q->where(function ($subQuery) use ($query) {
            $subQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ar')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.en')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.fr')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.tr')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(name, '$.ur')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meaning, '$.ar')) LIKE ?", ["%{$query}%"])
                ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(meaning, '$.en')) LIKE ?", ["%{$query}%"])
                ->orWhere('number', 'LIKE', "%{$query}%");
        });

        // Filter by revelation place if provided
        if ($revelationPlace) {
            $q->where('revelation_place', $revelationPlace);
        }

        return $q->orderBy('number')->paginate($perPage);
    }

    /**
     * Get average verses per surah
     *
     * @return float
     */
    public function getAverageVersesPerSurah(): float
    {
        return Cache::remember(
            'quran_avg_verses',
            self::CACHE_TTL,
            fn () => round($this->model->avg('verses_count'), 2)
        );
    }
}
