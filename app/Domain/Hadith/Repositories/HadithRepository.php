<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
        return new Hadith();
    }

    /**
     * {@inheritdoc}
     */
    public function getByBook(int $bookId, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->byBook($bookId)
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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
            ->orderBy('book_id')
            ->orderBy('hadith_number');

        if (!empty($relations)) {
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

            if (!empty($relations)) {
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
            return $this->model->with(['book', 'chapter', 'narrators'])
                ->find($id);
        });
    }

    /**
     * Advanced filter with multiple criteria
     *
     * @param array<string, mixed> $filters
     * @param int $perPage
     * @param array<string> $relations
     * @return LengthAwarePaginator
     */
    public function filterHadith(array $filters, int $perPage = 15, array $relations = []): LengthAwarePaginator
    {
        $query = $this->model->query();

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

        if (!empty($relations)) {
            $query->with($relations);
        }

        return $query->orderBy('book_id')
            ->orderBy('hadith_number')
            ->paginate($perPage);
    }
}

