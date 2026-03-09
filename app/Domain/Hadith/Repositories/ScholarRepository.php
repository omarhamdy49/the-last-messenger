<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithScholar;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Scholar Repository Implementation
 *
 * Data access layer for Hadith Scholar entities.
 */
class ScholarRepository extends BaseRepository implements ScholarRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new HadithScholar();
    }

    /**
     * {@inheritdoc}
     */
    public function getByName(string $name, string $locale = 'ar'): ?HadithScholar
    {
        $nameField = 'name_' . $locale;

        return Cache::remember(
            "hadith_scholar_name_{$locale}_{$name}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where($nameField, $name)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllByEra(): Collection
    {
        return Cache::remember(
            'hadith_scholars_by_era',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'name_ar', 'name_en', 'kunya', 'school', 'death_year_hijri', 'region'])
                ->orderBy('death_year_hijri')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('name_ar', 'LIKE', "%{$query}%")
            ->orWhere('name_en', 'LIKE', "%{$query}%")
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getWithGradingCount(): Collection
    {
        return Cache::remember(
            'hadith_scholars_with_grading_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'name_ar', 'name_en', 'kunya', 'school', 'death_year_hijri'])
                ->withCount('gradings')
                ->orderBy('gradings_count', 'desc')
                ->get()
        );
    }
}
