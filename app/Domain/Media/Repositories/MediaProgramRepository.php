<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class MediaProgramRepository extends BaseRepository implements MediaProgramRepositoryInterface
{
    private const CACHE_TTL = 1800; // 30 minutes

    /**
     * Allowed sort columns — whitelist prevents SQL injection via dynamic ordering.
     */
    private const ALLOWED_SORT_COLUMNS = ['display_order', 'views_count', 'created_at', 'rating'];

    protected function getModel(): Model
    {
        return new MediaProgram;
    }

    public function paginatePrograms(
        int $perPage = 15,
        ?string $category = null,
        string $sortBy = 'display_order',
        string $sortDir = 'asc',
        ?string $search = null,
        bool $publishedOnly = false,
        ?string $status = null
    ): LengthAwarePaginator {
        // Whitelist sort column to prevent injection
        $sortBy = in_array($sortBy, self::ALLOWED_SORT_COLUMNS, true) ? $sortBy : 'display_order';
        $sortDir = $sortDir === 'desc' ? 'desc' : 'asc';

        $query = MediaProgram::query()
            ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'total_seasons', 'total_episodes', 'rating', 'is_featured', 'is_trending', 'is_published', 'publish_at', 'views_count', 'display_order', 'created_at'])
            ->with(['translations:id,media_program_id,locale,title,is_ready', 'media']);

        if ($category !== null) {
            $query->where('category', $category);
        }

        if ($search !== null && $search !== '') {
            $query->whereHas('translations', function ($q) use ($search) {
                $q->where('title', 'LIKE', "%{$search}%")
                    ->orWhere('description', 'LIKE', "%{$search}%");
            });
        }

        if ($publishedOnly) {
            $query->published();
        }

        match ($status) {
            'published' => $query->where('is_published', true),
            'scheduled' => $query->where('is_published', false)->whereNotNull('publish_at'),
            'draft' => $query->where('is_published', false)->whereNull('publish_at'),
            default => null,
        };

        return $query->orderBy($sortBy, $sortDir)->paginate($perPage);
    }

    public function publishDue(): int
    {
        $due = MediaProgram::query()
            ->select(['id', 'slug'])
            ->where('is_published', false)
            ->whereNotNull('publish_at')
            ->where('publish_at', '<=', now())
            ->get();

        if ($due->isEmpty()) {
            return 0;
        }

        MediaProgram::query()
            ->whereIn('id', $due->pluck('id'))
            ->update(['is_published' => true]);

        // Bulk update bypasses BaseRepository::update(), so invalidate both
        // the versioned base cache (find/findBy) and the media list caches.
        $this->clearCache();
        foreach ($due as $program) {
            $this->clearCacheForModel($program->id);
        }

        return $due->count();
    }

    public function getPopular(int $limit = 12): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programTrending($limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->orderByDesc('views_count')
                ->limit($limit)
                ->get()
        );
    }

    public function getRecent(int $limit = 12): Collection
    {
        // Column list and withCount aligned with the sibling row-builders: this
        // was the only one of the six missing them, so /media/programs/recent
        // emitted null for maturity_rating/year/is_featured/is_trending and
        // omitted episodes_count entirely.
        return MediaProgram::query()
            ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'is_featured', 'is_trending', 'views_count', 'display_order', 'created_at'])
            ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
            ->withCount('episodes')
            ->published()
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getFeatured(int $limit = 6): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programFeatured($limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'is_featured', 'is_trending', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,description,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->where('is_featured', true)
                ->orderBy('display_order')
                ->limit($limit)
                ->get()
        );
    }

    public function getFeaturedForHero(int $limit = 5): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programsHero($limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'is_featured', 'is_trending', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,description,host,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->where('is_featured', true)
                ->orderBy('display_order')
                ->limit($limit)
                ->get()
        );
    }

    public function getTrendingFlagged(int $limit = 12): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programsTrendingFlagged($limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->where('is_trending', true)
                ->orderBy('display_order')
                ->limit($limit)
                ->get()
        );
    }

    public function getTrendingByScore(int $limit = 12): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programsTrendingByScore($limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'trending_score', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->where('trending_score', '>', 0)
                ->orderByDesc('trending_score')
                ->limit($limit)
                ->get()
        );
    }

    public function getByCategory(string $category, int $limit = 12): Collection
    {
        return Cache::remember(
            MediaCacheKeys::programsRow($category, $limit),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'is_trending', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->where('category', $category)
                ->orderBy('display_order')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Fetch every published program across the given categories in ONE query
     * (no N+1 row-by-row loop), then group by category and cap each row.
     *
     * Returns a map keyed by the category value, each holding an ordered
     * collection of at most {$perCategory} programs (display_order ascending).
     * Categories with no published programs are simply absent from the map.
     *
     * @param  list<string>  $categoryKeys
     * @return SupportCollection<string, Collection<int, MediaProgram>>
     */
    public function getPublishedGroupedByCategories(array $categoryKeys, int $perCategory = 12): SupportCollection
    {
        if ($categoryKeys === []) {
            return new SupportCollection;
        }

        return Cache::remember(
            MediaCacheKeys::programsGroupedByCategories($categoryKeys, $perCategory),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'rating', 'is_trending', 'views_count', 'display_order', 'created_at'])
                ->with(['translations:id,media_program_id,locale,title,is_ready', 'media'])
                ->withCount('episodes')
                ->published()
                ->whereIn('category', $categoryKeys)
                ->orderBy('display_order')
                ->get()
                ->groupBy(fn (MediaProgram $program): string => $program->category->value)
                ->map(fn (Collection $group): Collection => $group->take($perCategory)->values())
        );
    }

    public function search(string $term, int $perPage = 15, bool $publishedOnly = false): LengthAwarePaginator
    {
        return MediaProgram::query()
            // Same column set as paginatePrograms() — the public search results
            // feed the same card component as the index endpoint.
            ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'total_seasons', 'total_episodes', 'rating', 'is_featured', 'is_trending', 'is_published', 'publish_at', 'views_count', 'display_order', 'created_at'])
            ->with(['translations:id,media_program_id,locale,title,description,is_ready', 'media'])
            ->whereHas('translations', function ($q) use ($term) {
                $q->where('title', 'LIKE', "%{$term}%")
                    ->orWhere('description', 'LIKE', "%{$term}%");
            })
            ->when($publishedOnly, fn ($q) => $q->published())
            ->orderBy('display_order')
            ->paginate($perPage);
    }

    public function findBySlug(string $slug): ?MediaProgram
    {
        return Cache::remember(
            MediaCacheKeys::programBySlug($slug),
            self::CACHE_TTL,
            fn () => MediaProgram::query()
                ->select(['id', 'slug', 'category', 'maturity_rating', 'year', 'total_seasons', 'total_episodes', 'rating', 'is_featured', 'is_trending', 'is_published', 'publish_at', 'views_count', 'display_order', 'created_at', 'updated_at'])
                ->with(['translations', 'media'])
                ->published()
                ->where('slug', $slug)
                ->first()
        );
    }

    public function getFeaturedCategoryKeys(): array
    {
        return Cache::remember(
            MediaCacheKeys::featuredCategoryKeys(),
            self::CACHE_TTL,
            function (): array {
                // Unseeded table → every category stays visible (safe default).
                if (! MediaCategory::query()->exists()) {
                    return array_column(ProgramCategory::cases(), 'value');
                }

                return MediaCategory::query()
                    ->select(['key'])
                    ->featured()
                    ->ordered()
                    ->pluck('key')
                    ->all();
            }
        );
    }

    public function getAllCategoriesWithProgramCounts(): Collection
    {
        return MediaCategory::query()
            ->select(['media_categories.id', 'media_categories.key', 'media_categories.name', 'media_categories.description', 'media_categories.icon', 'media_categories.color', 'media_categories.is_featured', 'media_categories.display_order'])
            ->selectSub(
                MediaProgram::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('media_programs.category', 'media_categories.key'),
                'programs_count'
            )
            ->selectSub(
                MediaProgram::query()
                    ->selectRaw('COALESCE(SUM(views_count), 0)')
                    ->whereColumn('media_programs.category', 'media_categories.key'),
                'total_views'
            )
            ->ordered()
            ->get();
    }

    public function setCategoryFeatured(string $key, bool $isFeatured): void
    {
        MediaCategory::query()
            ->where('key', $key)
            ->update(['is_featured' => $isFeatured]);

        Cache::forget(MediaCacheKeys::featuredCategoryKeys());
    }

    public function findCategoryByKey(string $key): ?MediaCategory
    {
        return MediaCategory::query()
            ->select(['media_categories.id', 'media_categories.key', 'media_categories.name', 'media_categories.description', 'media_categories.icon', 'media_categories.color', 'media_categories.is_featured', 'media_categories.display_order'])
            ->selectSub(
                MediaProgram::query()
                    ->selectRaw('COUNT(*)')
                    ->whereColumn('media_programs.category', 'media_categories.key'),
                'programs_count'
            )
            ->selectSub(
                MediaProgram::query()
                    ->selectRaw('COALESCE(SUM(views_count), 0)')
                    ->whereColumn('media_programs.category', 'media_categories.key'),
                'total_views'
            )
            ->where('key', $key)
            ->first();
    }

    public function updateCategory(string $key, array $attributes): bool
    {
        $category = MediaCategory::query()->where('key', $key)->first();

        if ($category === null) {
            return false;
        }

        $category->fill($attributes)->save();
        Cache::forget(MediaCacheKeys::featuredCategoryKeys());

        return true;
    }

    public function deleteCategory(string $key): bool
    {
        $hasPrograms = MediaProgram::query()->where('category', $key)->exists();

        if ($hasPrograms) {
            return false;
        }

        MediaCategory::query()->where('key', $key)->delete();
        Cache::forget(MediaCacheKeys::featuredCategoryKeys());

        return true;
    }

    public function reorderCategories(array $orderedKeys): void
    {
        foreach (array_values($orderedKeys) as $index => $key) {
            MediaCategory::query()
                ->where('key', $key)
                ->update(['display_order' => $index]);
        }

        Cache::forget(MediaCacheKeys::featuredCategoryKeys());
    }

    public function clearCacheForModel(int $id): void
    {
        $program = MediaProgram::find($id, ['id', 'slug']);
        if (! $program) {
            return;
        }

        Cache::forget(MediaCacheKeys::programBySlug($program->slug));
        Cache::forget(MediaCacheKeys::programFeatured(6));
        Cache::forget(MediaCacheKeys::programFeatured(12));
        Cache::forget(MediaCacheKeys::programTrending(12));
        Cache::forget(MediaCacheKeys::programsHero(5));
        Cache::forget(MediaCacheKeys::programsTrendingFlagged(12));
        Cache::forget(MediaCacheKeys::programsTrendingByScore(12));
        foreach (ProgramCategory::cases() as $category) {
            Cache::forget(MediaCacheKeys::programsRow($category->value, 12));
        }
        // Grouped category rows are keyed by the current featured-category set;
        // forget the entry index() would build so edits don't serve stale rows.
        Cache::forget(MediaCacheKeys::programsGroupedByCategories($this->getFeaturedCategoryKeys(), 12));
        Cache::forget(MediaCacheKeys::stats());
    }

    public function getIndexStats(): array
    {
        $row = DB::table('media_programs')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN is_featured = 1 THEN 1 ELSE 0 END) as featured,
                SUM(CASE WHEN is_trending = 1 THEN 1 ELSE 0 END) as trending,
                SUM(views_count) as total_views
            ')
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'featured' => (int) ($row->featured ?? 0),
            'trending' => (int) ($row->trending ?? 0),
            'total_views' => (int) ($row->total_views ?? 0),
        ];
    }
}
