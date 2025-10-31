<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Repository Implementation
 * Handles all database operations for hadith with optimization and caching
 */
class HadithRepository extends BaseRepository implements HadithRepositoryInterface
{
    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new Hadith;
    }

    /**
     * {@inheritdoc}
     */
    public function getByBook(int $bookId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->byBook($bookId)
            ->active()
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByChapter(int $chapterId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->byChapter($chapterId)
            ->active()
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByGrade(HadithGrade $grade, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->byGrade($grade)
            ->active()
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getSahih(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->sahih()
            ->active()
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getReliable(int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->reliable()
            ->active()
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $searchQuery = $this->model->search($query)
            ->active()
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $searchQuery->with($relations);
        }

        return $searchQuery->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function findByBookAndNumber(int $bookId, int $hadithNumber, array $relations = []): ?Hadith
    {
        $cacheKey = $this->getCacheKey('findByBookAndNumber', compact('bookId', 'hadithNumber', 'relations'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($bookId, $hadithNumber, $relations) {
            $query = $this->model->where('book_id', $bookId)
                ->where('hadith_number', $hadithNumber);

            if (! empty($relations)) {
                $query->with($relations);
            }

            return $query->first();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function incrementViews(int $id): bool
    {
        $result = $this->model->where('id', $id)->increment('views_count');
        $this->clearCache();

        return $result > 0;
    }

    /**
     * {@inheritdoc}
     */
    public function getWithNarrators(int $id): ?Hadith
    {
        $cacheKey = $this->getCacheKey('withNarrators', compact('id'));

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($id) {
            return $this->model->with(['book', 'chapter'])
                ->find($id);
        });
    }

    /**
     * Advanced filter with multiple criteria
     *
     * @param  array<string, mixed>  $filters
     * @param  array<string>  $relations
     */
    public function filterHadith(array $filters, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->active();

        if (isset($filters['book_id'])) {
            $query->byBook($filters['book_id']);
        }

        if (isset($filters['chapter_id'])) {
            $query->byChapter($filters['chapter_id']);
        }

        if (isset($filters['grade'])) {
            $query->byGrade(HadithGrade::from($filters['grade']));
        }

        if (isset($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by narrator
        if (isset($filters['narrator_id'])) {
            $query->whereHas('narrators', function ($q) use ($filters) {
                $q->where('hadith_narrators.id', (int) $filters['narrator_id']);
            });
        }

        // Filter by date range
        if (isset($filters['start_date']) && $filters['start_date']) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date']) && $filters['end_date']) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->orderBy('book_id')
            ->orderBy('hadith_number')
            ->paginate($perPage);
    }

    /**
     * Get hadith by narrator
     */
    public function getByNarrator(int $narratorId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->whereHas('narrators', function ($q) use ($narratorId) {
            $q->where('hadith_narrators.id', $narratorId);
        })->active()->orderBy('book_id')->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get hadith by date range
     */
    public function getByDateRange(string $startDate, string $endDate, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->whereBetween('created_at', [$startDate, $endDate])
            ->active()
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (! empty($relations)) {
            $query->with($relations);
        }

        return $query->paginate($perPage);
    }
}
