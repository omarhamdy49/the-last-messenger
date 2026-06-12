<?php

declare(strict_types=1);

namespace App\Domain\Blog\Repositories;

use App\Domain\Blog\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * TagRepository
 *
 * Data access layer for tags
 */
class TagRepository implements TagRepositoryInterface
{
    /**
     * Model class
     */
    protected string $model = Tag::class;

    /**
     * Get base query with eager loaded relationships
     */
    protected function getBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model::query()->withCount('articles');
    }

    /**
     * Get paginated tags with search and filters
     */
    public function paginate(
        int $perPage = 50,
        string $search = '',
        array $filters = [],
        array $with = []
    ): LengthAwarePaginator {
        $query = $this->getBaseQuery();

        // Apply search across all language fields (via translation table)
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                // Search in translation table
                $q->whereHas('translations', function ($sub) use ($search) {
                    $sub->where(function ($inner) use ($search) {
                        $inner->where('name', 'LIKE', "%{$search}%")
                            ->orWhere('description', 'LIKE', "%{$search}%");
                    });
                })
                // Also search slug directly on tags table
                    ->orWhere('slug', 'like', "%{$search}%");
            });
        }

        // Apply type filter
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Apply date range filter
        if (! empty($filters['date_from'])) {
            $query->where('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->where('created_at', '<=', $filters['date_to']);
        }

        // Apply additional eager loading
        if (! empty($with)) {
            $query->with($with);
        }

        // Order by usage count (most used first) by default
        $query->orderBy('articles_count', 'desc')->orderBy('created_at', 'desc');

        return $query->paginate($perPage);
    }

    /**
     * Create new tag
     */
    public function create(array $data): Model
    {
        $tag = $this->model::create($data);
        Cache::forget('tags:count');
        Cache::forget('tags:total_usage');
        Cache::forget('tags:most_used');

        return $tag;
    }

    /**
     * Update tag
     */
    public function update(int $id, array $data): Model
    {
        $tag = $this->findOrFail($id);
        $tag->update($data);
        Cache::forget('tags:count');
        Cache::forget('tags:total_usage');
        Cache::forget('tags:most_used');

        return $tag->fresh();
    }

    /**
     * Delete tag
     */
    public function delete(int $id): bool
    {
        $tag = $this->findOrFail($id);

        // Detach all articles before deletion
        $tag->articles()->detach();

        $result = $tag->delete();
        Cache::forget('tags:count');
        Cache::forget('tags:total_usage');
        Cache::forget('tags:most_used');

        return $result;
    }

    /**
     * Bulk delete tags
     */
    public function bulkDelete(array $ids): int
    {
        // Detach all articles from these tags first
        DB::table('taggables')
            ->where('taggable_type', 'App\\Domain\\Blog\\Models\\Article')
            ->whereIn('tag_id', $ids)
            ->delete();

        $count = $this->model::whereIn('id', $ids)->delete();
        Cache::forget('tags:count');
        Cache::forget('tags:total_usage');
        Cache::forget('tags:most_used');

        return $count;
    }

    /**
     * Find tag by ID or fail
     */
    public function findOrFail(int $id): Model
    {
        return $this->model::findOrFail($id);
    }

    /**
     * Get total count
     */
    public function count(): int
    {
        return Cache::remember(
            'tags:count',
            now()->addMinutes(15),
            fn () => $this->model::count()
        );
    }

    /**
     * Get total usage count (total articles using any tag)
     */
    public function getTotalUsageCount(): int
    {
        return Cache::remember(
            'tags:total_usage',
            now()->addMinutes(15),
            fn () => DB::table('taggables')
                ->where('taggable_type', 'App\\Domain\\Blog\\Models\\Article')
                ->distinct('taggable_id')
                ->count('taggable_id')
        );
    }

    /**
     * Get most used tag
     */
    public function getMostUsedTag(): ?Model
    {
        return Cache::remember(
            'tags:most_used',
            now()->addMinutes(15),
            fn () => $this->model::withCount('articles')
                ->orderBy('articles_count', 'desc')
                ->first()
        );
    }

    /**
     * Get recently added tags count
     */
    public function getRecentlyAddedCount(int $days = 7): int
    {
        return $this->model::where('created_at', '>=', now()->subDays($days))->count();
    }

    /**
     * Get usage count for specific tag
     */
    public function getUsageCount(int $tagId): int
    {
        $tag = $this->findOrFail($tagId);

        return $tag->articles()->count();
    }

    /**
     * Get published articles count for tag
     */
    public function getPublishedArticlesCount(int $tagId): int
    {
        $tag = $this->findOrFail($tagId);

        return $tag->articles()->where('status', 'published')->count();
    }

    /**
     * Get articles for tag (paginated)
     */
    public function getArticlesForTag(int $tagId, int $perPage = 20): LengthAwarePaginator
    {
        $tag = $this->findOrFail($tagId);

        return $tag->articles()
            ->with(['writer:id,name', 'category:id,name,slug'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find duplicate tag by name
     *
     * Checks if any language name matches an existing tag
     */
    public function findDuplicate(array $name): ?Model
    {
        $query = $this->model::query();

        $query->whereHas('translations', function ($q) use ($name) {
            $q->where(function ($inner) use ($name) {
                foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $lang) {
                    if (! empty($name[$lang])) {
                        $inner->orWhere('name', $name[$lang]);
                    }
                }
            });
        });

        return $query->first();
    }

    /**
     * Search tags for autocomplete
     */
    public function searchForAutocomplete(string $query, ?string $type = null, int $limit = 10): \Illuminate\Support\Collection
    {
        $searchQuery = $this->model::query();

        // Search across all language fields (via translation table)
        $searchQuery->where(function ($q) use ($query) {
            $q->whereHas('translations', function ($sub) use ($query) {
                $sub->where(function ($inner) use ($query) {
                    $inner->where('name', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%");
                });
            })
                ->orWhere('slug', 'like', "%{$query}%");
        });

        // Filter by type if provided
        if ($type !== null) {
            $searchQuery->where('type', $type);
        }

        // Order by usage count (most used first)
        $searchQuery->orderByDesc('usage_count');

        return $searchQuery->limit($limit)->get();
    }

    /**
     * Get unused tags (zero usage_count)
     */
    public function getUnusedTags(int $perPage = 50): LengthAwarePaginator
    {
        return $this->model::where('usage_count', 0)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get paginated tags filtered by type, with locale-aware JSON name search
     *
     * Uses JSON_EXTRACT so each locale column of the JSON `name` field is
     * searched independently — identical to the original controller logic.
     */
    public function paginateByType(
        string $type,
        int $perPage = 15,
        string $search = '',
        array $locales = ['ar', 'en'],
        string $sortBy = 'created_at',
        string $sortOrder = 'desc'
    ): LengthAwarePaginator {
        $query = $this->model::where('type', $type);

        if (! empty($search)) {
            $query->where(function ($q) use ($search, $locales) {
                foreach ($locales as $locale) {
                    $q->orWhereRaw("JSON_EXTRACT(name, '$.{$locale}') LIKE ?", ["%{$search}%"]);
                }
            });
        }

        $query->orderBy($sortBy, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Search tags by type for autocomplete
     */
    public function searchByType(
        string $type,
        string $search,
        int $limit = 10,
        array $columns = ['id', 'slug', 'name', 'color']
    ): \Illuminate\Support\Collection {
        return $this->model::where('type', $type)
            ->where(function ($q) use ($search) {
                // Reuse the JSON extract approach for consistency across all locales
                foreach (['ar', 'en', 'fr', 'tr', 'ur'] as $locale) {
                    $q->orWhereRaw("JSON_EXTRACT(name, '$.{$locale}') LIKE ?", ["%{$search}%"]);
                }
            })
            ->limit($limit)
            ->get($columns);
    }

    /**
     * Recalculate usage count for all tags
     *
     * @return int Number of tags updated
     */
    public function recalculateAllUsageCounts(): int
    {
        $tags = $this->model::select(['id', 'usage_count'])->get();
        $updated = 0;

        foreach ($tags as $tag) {
            $count = DB::table('taggables')
                ->where('tag_id', $tag->id)
                ->count();

            if ($tag->usage_count !== $count) {
                $tag->update(['usage_count' => $count]);
                $updated++;
            }
        }

        Cache::forget('tags:count');
        Cache::forget('tags:total_usage');
        Cache::forget('tags:most_used');

        return $updated;
    }
}
