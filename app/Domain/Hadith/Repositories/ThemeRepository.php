<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithTheme;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
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
        return new HadithTheme;
    }

    /**
     * {@inheritdoc}
     */
    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithTheme
    {
        $slugField = 'slug_'.$locale;

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
                ->select(['id', 'theme_ar', 'theme_en', 'parent_id'])
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
    public function paginateHadithsByIdentifier(string $identifier, int $perPage = 20): ?LengthAwarePaginator
    {
        $theme = $this->model
            ->newQuery()
            ->select(['id'])
            ->when(
                ctype_digit($identifier),
                fn ($query) => $query->whereKey((int) $identifier),
                fn ($query) => $query->where(function ($query) use ($identifier) {
                    $query->where('theme_ar', $identifier)
                        ->orWhere('theme_en', $identifier)
                        ->orWhereHas('translations', fn ($translation) => $translation->where('name', $identifier));
                })
            )
            ->first();

        return $theme?->hadiths()
            ->with([
                'translations:id,hadith_id,language_code,text',
                'themes:id,theme_ar,theme_en',
            ])
            ->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number')
            ->paginate(
                $perPage,
                ['hadith_hadiths.id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id']
            );
    }

    /**
     * {@inheritdoc}
     *
     * FULLTEXT index on hadith_themes covers (theme_ar, theme_en).
     * Note: the real DB columns are theme_ar / theme_en (not name_ar / name_en).
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return FullTextSearchHelper::apply(
            $this->model->newQuery(),
            ['theme_ar', 'theme_en'],
            $query
        )->paginate($perPage);
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
                ->select(['id', 'theme_ar', 'theme_en', 'parent_id'])
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
        $children = $this->model->select(['id', 'parent_id'])->where('parent_id', $themeId)->get();

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

        // Apply search — FULLTEXT index covers (theme_ar, theme_en) on hadith_themes.
        if (! empty($search)) {
            FullTextSearchHelper::apply($query, ['theme_ar', 'theme_en'], $search);
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
