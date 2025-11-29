<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Collection Repository Implementation
 *
 * Data access layer for Hadith Collection entities.
 */
class CollectionRepository extends BaseRepository implements CollectionRepositoryInterface
{
    /**
     * Cache TTL in seconds (1 hour - collections rarely change)
     */
    private const CACHE_TTL = 3600;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new HadithCollection();
    }

    /**
     * Get all collections (cached)
     */
    public function all(array $columns = ['*'], array $relations = []): Collection
    {
        return Cache::remember(
            'hadith_collections_all',
            self::CACHE_TTL,
            fn () => $this->model
                ->with($relations)
                ->orderBy('id')
                ->get($columns)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBySlug(string $slug, string $locale = 'ar'): ?HadithCollection
    {
        $slugField = 'slug_' . $locale;

        return Cache::remember(
            "hadith_collection_slug_{$locale}_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where($slugField, $slug)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByAbbreviation(string $abbreviation): ?HadithCollection
    {
        return Cache::remember(
            "hadith_collection_abbr_{$abbreviation}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('abbreviation', $abbreviation)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getAllWithBooksCount(): Collection
    {
        return Cache::remember(
            'hadith_collections_with_books_count',
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount('books')
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithBooks(int $id): ?HadithCollection
    {
        return Cache::remember(
            "hadith_collection_{$id}_with_books",
            self::CACHE_TTL,
            fn () => $this->model
                ->with(['books' => fn ($q) => $q->orderBy('id')])
                ->find($id)
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
            ->orWhere('abbreviation', 'LIKE', "%{$query}%")
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getWithStatistics(): Collection
    {
        return Cache::remember(
            'hadith_collections_statistics',
            self::CACHE_TTL,
            fn () => $this->model
                ->withCount(['books'])
                ->withCount(['books as hadiths_count' => function ($query) {
                    $query->selectRaw('(SELECT COUNT(*) FROM hadith_hadiths WHERE hadith_hadiths.book_id = hadith_books.id)');
                }])
                ->get()
        );
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

        // Apply search
        if (!empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                    ->orWhere('name_en', 'LIKE', "%{$search}%")
                    ->orWhere('abbreviation', 'LIKE', "%{$search}%");
            });
        }

        // Apply active filter
        if (isset($filters['only_active']) && $filters['only_active']) {
            $query->where('is_active', true);
        }

        // Apply sorting
        $direction = $filters['sort_direction'] ?? 'asc';
        $query->orderBy($sortBy, $direction);

        // Include counts
        $query->withCount('books');

        return $query->paginate($perPage);
    }
}
