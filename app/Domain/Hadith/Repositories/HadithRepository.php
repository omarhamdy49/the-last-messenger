<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

/**
 * Hadith Repository Implementation
 *
 * Data access layer for Hadith entities with caching.
 */
class HadithRepository extends BaseRepository implements HadithRepositoryInterface
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
        return new Hadith();
    }

    /**
     * {@inheritdoc}
     */
    public function getByBookAndNumber(int $bookId, string $hadithNumber): ?Hadith
    {
        return Cache::remember(
            "hadith_book_{$bookId}_number_{$hadithNumber}",
            self::CACHE_TTL,
            fn () => $this->model
                ->where('book_id', $bookId)
                ->where('hadith_number', $hadithNumber)
                ->with(['translations', 'book.collection'])
                ->first()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByBook(int $bookId): Collection
    {
        return Cache::remember(
            "hadiths_by_book_{$bookId}",
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
                ->byBook($bookId)
                ->with('translations')
                ->orderBy('hadith_number')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByGrading(string $gradingStatus): Collection
    {
        return $this->model
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->byGrading($gradingStatus)
            ->with('translations')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuthentic(): Collection
    {
        return Cache::remember(
            'hadiths_authentic',
            self::CACHE_TTL,
            fn () => $this->model
                ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
                ->authentic()
                ->with('translations')
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getWithAgreement(): Collection
    {
        return $this->model
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->withAgreement()
            ->with('translations')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByTheme(int $themeId): Collection
    {
        return $this->model
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->whereHas('themes', fn ($q) => $q->where('hadith_theme_id', $themeId))
            ->with(['translations', 'themes'])
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByNarrator(int $companionId): Collection
    {
        return $this->model
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->where('companion_id', $companionId)
            ->with(['translations', 'companion:id,name_ar,name_en'])
            ->orderBy('hadith_number')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = $this->model->query();

        // Search in translations
        $q->whereHas('translations', function ($subQuery) use ($query) {
            $subQuery->where('text', 'LIKE', "%{$query}%")
                ->orWhere('translation', 'LIKE', "%{$query}%");
        });

        // Apply filters
        if (! empty($filters['book_id'])) {
            $q->where('book_id', $filters['book_id']);
        }

        if (! empty($filters['grading_status'])) {
            $q->where('grading_status', $filters['grading_status']);
        }

        if (! empty($filters['collection_id'])) {
            $q->whereHas('book', fn ($b) => $b->where('collection_id', $filters['collection_id']));
        }

        return $q->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->with(['translations', 'book:id,collection_id,title_ar,title_en', 'book.collection:id,name_ar,name_en,abbreviation'])
            ->orderBy('hadith_number')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'hadith_number',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->model->query();

        // Apply search
        if (! empty($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('hadith_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('translations', function ($t) use ($search) {
                        $t->where('text', 'LIKE', "%{$search}%")
                            ->orWhere('translation', 'LIKE', "%{$search}%");
                    });
            });
        }

        // Apply filters
        if (! empty($filters['book_id'])) {
            $query->where('book_id', $filters['book_id']);
        }

        if (! empty($filters['grading_status'])) {
            $query->where('grading_status', $filters['grading_status']);
        }

        if (! empty($filters['grading_agreement'])) {
            $query->where('grading_agreement', true);
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('book', fn ($q) => $q->where('collection_id', $filters['collection_id']));
        }

        // Apply sorting
        $allowedSorts = ['hadith_number', 'grading_status', 'created_at', 'book_id'];
        $sortBy = in_array($sortBy, $allowedSorts, true) ? $sortBy : 'hadith_number';
        $query->orderBy($sortBy);

        return $query->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->with(['translations', 'book:id,collection_id,title_ar,title_en'])
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function countByGrading(string $gradingStatus): int
    {
        return Cache::remember(
            "hadiths_count_grading_{$gradingStatus}",
            self::CACHE_TTL,
            fn () => $this->model->where('grading_status', $gradingStatus)->count()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getRandom(?string $gradingStatus = null): ?Hadith
    {
        $query = $this->model->query();

        if ($gradingStatus !== null) {
            $query->where('grading_status', $gradingStatus);
        }

        return $query->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement', 'companion_id'])
            ->with('translations')
            ->inRandomOrder()
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getWithFullRelations(int $id): ?Hadith
    {
        return $this->model
            ->withFullRelations()
            ->find($id);
    }
}
