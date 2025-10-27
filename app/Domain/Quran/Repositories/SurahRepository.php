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
     *
     * @return Model
     */
    protected function getModel(): Model
    {
        return new Surah();
    }

    /**
     * Get all surahs (cached)
     *
     * @param array $columns
     * @param array $relations
     * @return Collection
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return Cache::remember(
            'quran_surahs_all',
            self::CACHE_TTL,
            fn() => $this->model
                ->with($relations)
                ->orderBy('number')
                ->get($columns)
        );
    }

    /**
     * Get surah by number (1-114)
     *
     * @param int $number
     * @return Surah|null
     */
    public function getByNumber(int $number): ?Surah
    {
        return Cache::remember(
            "quran_surah_number_{$number}",
            self::CACHE_TTL,
            fn() => $this->model->where('number', $number)->first()
        );
    }

    /**
     * Get surahs by revelation place
     *
     * @param RevelationPlace|string $place
     * @return Collection
     */
    public function getByRevelationPlace(RevelationPlace|string $place): Collection
    {
        $placeValue = $place instanceof RevelationPlace ? $place->value : $place;
        
        return Cache::remember(
            "quran_surahs_revelation_{$placeValue}",
            self::CACHE_TTL,
            fn() => $this->model
                ->byRevelationPlace($place)
                ->orderBy('number')
                ->get()
        );
    }

    /**
     * Get surahs by Juz
     *
     * @param int $juz
     * @return Collection
     */
    public function getByJuz(int $juz): Collection
    {
        return Cache::remember(
            "quran_surahs_juz_{$juz}",
            self::CACHE_TTL,
            fn() => $this->model
                ->byJuz($juz)
                ->orderBy('number')
                ->get()
        );
    }
}

