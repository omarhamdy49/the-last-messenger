<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Narrator Repository Implementation
 *
 * Data access layer for Hadith Narrator entities.
 */
class NarratorRepository extends BaseRepository implements NarratorRepositoryInterface
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
        return new HadithNarrator;
    }

    /**
     * {@inheritdoc}
     */
    public function getByName(string $name, string $locale = 'ar'): ?HadithNarrator
    {
        $allowedLocales = ['ar', 'en', 'fr', 'tr', 'ur'];
        $safeLocale = in_array($locale, $allowedLocales, true) ? $locale : 'ar';
        $nameField = 'name_'.$safeLocale;

        return Cache::remember(
            "hadith_narrator_name_{$safeLocale}_{$name}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where(function ($query) use ($nameField, $name, $safeLocale): void {
                    $query->where($nameField, $name)
                        ->orWhereHas('sahabi.translations', fn ($translation) => $translation
                            ->where('locale', $safeLocale)
                            ->where('name', $name));
                })
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithHadithCount(): Collection
    {
        return Cache::remember(
            'hadith_narrators_with_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'sahaba_id', 'name_ar', 'name_en', 'kunya', 'death_year_hijri', 'reliability_grade'])
                ->with('sahabi.translations')
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     *
     * FULLTEXT index on hadith_narrators covers (name_ar, name_en, full_name_ar, full_name_en, kunya).
     * MATCH must reference all columns in that composite index.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model->newQuery()->search($query)->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'name_ar',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();
        $query->with('sahabi.translations');

        // Apply column-level filters
        if (! empty($filters['reliability_grade'])) {
            $query->where('reliability_grade', $filters['reliability_grade']);
        }

        if (! empty($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (! empty($filters['school'])) {
            $query->where('school', $filters['school']);
        }

        // Apply search across all name variants
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('full_name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('full_name_en', 'LIKE', "%{$search}%")
                    ->orWhereHas('sahabi.translations', function ($translation) use ($search): void {
                        $translation->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('full_name', 'LIKE', "%{$search}%");
                    });
            });
        }

        $direction = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $direction);

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function restoreById(int $id): HadithNarrator
    {
        $narrator = HadithNarrator::withTrashed()->findOrFail($id);
        $narrator->restore();

        return $narrator;
    }

    /**
     * {@inheritdoc}
     */
    public function getTopByHadithCount(int $limit = 10): Collection
    {
        return Cache::remember(
            "hadith_narrators_top_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'sahaba_id', 'name_ar', 'name_en', 'kunya', 'death_year_hijri', 'reliability_grade'])
                ->with('sahabi.translations')
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->limit($limit)
                ->get()
        );
    }
}
