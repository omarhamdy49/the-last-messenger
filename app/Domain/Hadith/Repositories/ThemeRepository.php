<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Theme Repository Implementation
 *
 * Data access layer for Hadith Theme entities.
 */
class ThemeRepository extends BaseRepository implements ThemeRepositoryInterface
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
        return new HadithTheme();
    }

    /**
     * {@inheritdoc}
     */
    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithTheme
    {
        $slugField = 'slug_' . $locale;

        return Cache::remember(
            "hadith_theme_slug_{$locale}_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where($slugField, $slug)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithHadithCount(): Collection
    {
        return Cache::remember(
            'hadith_themes_with_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithHadiths(int $id): ?HadithTheme
    {
        return $this->model
            ->with(['hadiths' => fn ($q) => $q->with('translations')])
            ->find($id);
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
    public function getTopByHadithCount(int $limit = 10): Collection
    {
        return Cache::remember(
            "hadith_themes_top_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount('hadiths')
                ->orderBy('hadiths_count', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getChildrenIds(int $themeId): array
    {
        $ids = [];
        $children = $this->model->where('parent_id', $themeId)->get();

        foreach ($children as $child) {
            $ids[] = $child->id;
            // Recursively get grandchildren
            $grandchildIds = $this->getChildrenIds($child->id);
            $ids = array_merge($ids, $grandchildIds);
        }

        return $ids;
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'sort_order',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->newQuery();

        // Apply search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        // Apply root only filter
        if (isset($filters['only_root']) && $filters['only_root']) {
            $query->whereNull('parent_id');
        }

        // Apply sorting
        $direction = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $direction);

        // Include counts
        $query->withCount('hadiths');

        return $query->paginate($perPage);
    }
}
