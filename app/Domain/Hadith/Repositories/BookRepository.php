<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Book Repository Implementation
 *
 * Data access layer for Hadith Book entities.
 */
class BookRepository extends BaseRepository implements BookRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new HadithBook;
    }

    /**
     * {@inheritdoc}
     */
    public function getByCollection(int $collectionId): Collection
    {
        return Cache::remember(
            "hadith_books_collection_{$collectionId}",
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'collection_id', 'title_ar', 'title_en', 'book_number'])
                ->byCollection($collectionId)
                ->withCount('hadiths')
                ->orderBy('id')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getBySlug(int $collectionId, string $slug, string $locale = 'ar'): ?HadithBook
    {
        $allowedLocales = ['ar', 'en', 'fr', 'tr', 'ur'];
        $safeLocale = in_array($locale, $allowedLocales, true) ? $locale : 'ar';
        $slugField = 'slug_'.$safeLocale;

        return Cache::remember(
            "hadith_book_slug_{$collectionId}_{$safeLocale}_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('collection_id', $collectionId)
                ->where($slugField, $slug)
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithChapters(int $id): ?HadithBook
    {
        return Cache::remember(
            "hadith_book_{$id}_with_chapters",
            self::CACHE_TTL,
            fn () => $this->model
                ->with(['chapters' => fn ($q) => $q->orderBy('id')])
                ->find($id)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithHadiths(int $id): ?HadithBook
    {
        return $this->model
            ->with([
                'hadiths' => fn ($q) => $q->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number'),
                'hadiths.translations',
            ])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getWithFullRelations(int $id): ?HadithBook
    {
        return $this->model
            ->with([
                'collection:id,name_ar,name_en,abbreviation',
                'chapters:id,book_id,chapter_number,name,display_order',
                'hadiths' => fn ($q) => $q->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number'),
                'hadiths.translations',
            ])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'id',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->query();

        // Apply search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('title_ar', 'LIKE', "%{$search}%")
                    ->orWhere('title_en', 'LIKE', "%{$search}%");
            });
        }

        // Apply filters
        if (! empty($filters['collection_id'])) {
            $query->where('collection_id', $filters['collection_id']);
        }

        // Apply sorting
        $allowedSorts = ['id', 'title_ar', 'title_en', 'created_at'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'id';
        $query->orderBy($sortBy);

        return $query->select(['id', 'collection_id', 'title_ar', 'title_en', 'book_number', 'created_at'])
            ->with('collection:id,name_ar,name_en,abbreviation')
            ->withCount('hadiths')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function countHadiths(int $bookId): int
    {
        return Cache::remember(
            "hadith_book_{$bookId}_hadiths_count",
            self::CACHE_TTL,
            fn () => $this->model->find($bookId)?->hadiths()->count() ?? 0
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook
    {
        return $this->model
            ->where('collection_id', $collectionId)
            ->where('book_number', $bookNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAllForDropdown(?int $collectionId = null): Collection
    {
        $cacheKey = 'hadith_books_dropdown'.($collectionId ? "_collection_{$collectionId}" : '_all');

        return Cache::remember(
            $cacheKey,
            self::CACHE_TTL,
            function () use ($collectionId) {
                $query = $this->model
                    ->select(['id', 'title_ar', 'title_en', 'collection_id'])
                    ->orderBy('title_ar');

                if ($collectionId !== null) {
                    $query->where('collection_id', $collectionId);
                }

                return $query->get();
            }
        );
    }

    /**
     * {@inheritdoc}
     */
    public function paginateByCollection(
        int $collectionId,
        int $perPage = 15,
        string $sortBy = 'book_number',
        string $sortDirection = 'asc'
    ): LengthAwarePaginator {
        return $this->model
            ->where('collection_id', $collectionId)
            ->withCount(['hadiths', 'chapters'])
            ->orderBy($sortBy, $sortDirection)
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicByCollectionAndNumber(int $collectionId, int $bookNumber): ?HadithBook
    {
        return $this->model
            ->select(['id', 'collection_id', 'book_number', 'title_ar', 'title_en', 'slug_ar', 'slug_en', 'chapter_title_ar', 'chapter_title_en', 'created_at', 'updated_at'])
            // Aliased to the attribute names BookDTO reads, so mapping the DTO
            // does not fall back to per-model count queries.
            ->withCount(['hadiths as hadith_count', 'chapters as chapters_count'])
            ->with('collection:id,name_ar,name_en')
            ->where('collection_id', $collectionId)
            ->where('book_number', $bookNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getAdjacentByNumber(int $collectionId, int $bookNumber): array
    {
        $columns = ['id', 'collection_id', 'book_number', 'title_ar', 'title_en'];

        return [
            'previous' => $this->model
                ->select($columns)
                ->where('collection_id', $collectionId)
                ->where('book_number', '<', $bookNumber)
                ->orderByDesc('book_number')
                ->first(),
            'next' => $this->model
                ->select($columns)
                ->where('collection_id', $collectionId)
                ->where('book_number', '>', $bookNumber)
                ->orderBy('book_number')
                ->first(),
        ];
    }
}
