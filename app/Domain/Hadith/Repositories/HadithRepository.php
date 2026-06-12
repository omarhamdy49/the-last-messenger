<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
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
        return new Hadith;
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
                ->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number')
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
            ->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function search(string $query, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $q = $this->model->query();

        // Search in translations — FULLTEXT index covers the 'text' column only.
        // 'translation' has no FULLTEXT index so it keeps LIKE.
        $q->whereHas('translations', function ($subQuery) use ($query) {
            FullTextSearchHelper::apply($subQuery, ['text'], $query)
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
            ->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number')
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

        // Apply search. The hadith text lives in hadith_translations.text (FULLTEXT
        // indexed). Combining the numeric hadith_number LIKE with the text EXISTS via OR
        // defeats the FULLTEXT index (MySQL runs MATCH per row → full scan). Hadith
        // numbers are numeric, so we branch: numeric terms match hadith_number, textual
        // terms use the FULLTEXT path alone — keeping each query SARGable and fast.
        if (! empty($search)) {
            if (is_numeric($search)) {
                $query->where('hadith_number', 'LIKE', "%{$search}%");
            } else {
                $query->whereHas('translations', function ($t) use ($search) {
                    FullTextSearchHelper::apply($t, ['text'], $search);
                });
            }
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

    /**
     * {@inheritdoc}
     */
    public function listForAdmin(
        int $perPage = 20,
        string $search = '',
        array $filters = []
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model->query()
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'companion_id'])
            ->with([
                'book:id,title_ar,title_en,collection_id',
                'book.collection:id,name_ar,name_en',
                'translations' => fn ($q) => $q
                    ->select(['id', 'hadith_id', 'language_code', 'text'])
                    ->where('language_code', 'ar'),
            ]);

        if ($filters['collection_id'] ?? null) {
            $query->whereHas('book', fn ($q) => $q->where('collection_id', $filters['collection_id']));
        }

        if ($filters['book_id'] ?? null) {
            $query->where('book_id', $filters['book_id']);
        }

        if ($filters['grading_status'] ?? null) {
            $query->where('grading_status', $filters['grading_status']);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('hadith_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('book', fn ($bq) => $bq->where('title_ar', 'LIKE', "%{$search}%"))
                    ->orWhereHas('translations', fn ($tq) => $tq
                        ->where('language_code', 'ar')
                        ->where('text', 'LIKE', "%{$search}%"));
            });
        }

        // hadith_number is a string ("1", "10", "100", occasionally "1a") — a plain
        // string sort orders 1, 10, 100. Length-then-value gives numeric order on
        // both MySQL and the SQLite test driver without non-portable CASTs.
        return $query
            ->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function searchForAdmin(
        string $search = '',
        array $filters = [],
        int $perPage = 15,
        string $locale = 'ar'
    ): \Illuminate\Pagination\LengthAwarePaginator {
        $query = $this->model->query()
            ->select(['id', 'book_id', 'hadith_number', 'grading_status'])
            ->with([
                'book:id,collection_id,title_ar,title_en',
                'book.collection:id,name_ar,name_en,abbreviation',
                'translations' => fn ($q) => $q
                    ->select(['id', 'hadith_id', 'language_code', 'text'])
                    ->whereIn('language_code', array_unique([$locale, 'ar']))
                    ->orderByRaw("FIELD(language_code, ?, 'ar')", [$locale]),
            ]);

        if ($filters['collection_id'] ?? null) {
            $query->whereHas('book', fn ($q) => $q->where('collection_id', $filters['collection_id']));
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('hadith_number', 'LIKE', "%{$search}%")
                    ->orWhereHas('translations', fn ($tq) => $tq->where('text', 'LIKE', "%{$search}%"))
                    ->orWhereHas('book', fn ($bq) => $bq
                        ->where('title_ar', 'LIKE', "%{$search}%")
                        ->orWhere('title_en', 'LIKE', "%{$search}%"));
            });
        }

        return $query->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->model->whereIn('id', $ids)->delete();
        $this->clearCache();

        return (int) $count;
    }

    /**
     * {@inheritdoc}
     */
    public function paginatePublicByBook(
        int $bookId,
        int $perPage = 15,
        int $page = 1,
        ?string $gradingStatus = null,
        string $search = '',
        string $locale = 'ar'
    ): LengthAwarePaginator {
        $query = $this->model->query()
            ->select(['id', 'book_id', 'hadith_number', 'grading_status', 'grading_agreement'])
            ->where('book_id', $bookId)
            ->with([
                'book:id,collection_id,title_ar,title_en',
                'book.collection:id,name_ar,name_en',
                'translations' => fn ($q) => $q
                    ->select(['id', 'hadith_id', 'language_code', 'text'])
                    ->whereIn('language_code', array_unique([$locale, 'ar'])),
                // HadithDTO::fromModel reads themes unconditionally — eager load
                // so DTO mapping never lazy-loads per row.
                'themes:id,theme_ar,theme_en',
            ]);

        if ($gradingStatus !== null && $gradingStatus !== '') {
            $query->where('grading_status', $gradingStatus);
        }

        if ($search !== '') {
            // Parameterized LIKE — the query builder binds the term; only the
            // wildcards are concatenated. Scoped to this book via book_id above.
            $query->whereHas('translations', fn ($t) => $t->where('text', 'LIKE', '%'.$search.'%'));
        }

        return $query
            ->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number')
            ->paginate(perPage: $perPage, page: $page);
    }

    /**
     * {@inheritdoc}
     */
    public function getWithPublicDetail(int $id): ?Hadith
    {
        return $this->model->query()
            ->select([
                'id', 'book_id', 'companion_id', 'hadith_number', 'grading_status',
                'grading_agreement', 'significance_ar', 'significance_en',
                'historical_context_ar', 'historical_context_en', 'text_hash',
            ])
            ->with($this->publicDetailRelations())
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function getByTextHash(string $textHash, int $excludeHadithId): Collection
    {
        return $this->model->query()
            ->select([
                'id', 'book_id', 'hadith_number', 'grading_status',
                'grading_agreement', 'text_hash',
            ])
            ->where('text_hash', $textHash)
            ->whereKeyNot($excludeHadithId)
            ->with($this->publicDetailRelations())
            ->orderBy('id')
            ->limit(12)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function countByTextHash(string $textHash, int $excludeHadithId): int
    {
        return $this->model->query()
            ->where('text_hash', $textHash)
            ->whereKeyNot($excludeHadithId)
            ->count();
    }

    /**
     * Eager loads (explicit columns) shared by the public detail and
     * parallel-narration fetches. Child collections are ordered and capped so
     * a hadith with pathological relation counts cannot blow up the page.
     *
     * @return array<string, mixed>
     */
    private function publicDetailRelations(): array
    {
        return [
            'book:id,collection_id,book_number,title_ar,title_en',
            'book.collection:id,name_ar,name_en,slug_ar,slug_en',
            'translations' => fn ($q) => $q
                ->select(['id', 'hadith_id', 'language_code', 'text'])
                ->orderBy('language_code'),
            'isnads' => fn ($q) => $q
                ->select(['id', 'hadith_id', 'narrator_id', 'position_in_chain', 'transmission_method'])
                ->orderBy('position_in_chain')
                ->limit(30),
            'isnads.narrator:id,name_ar,name_en,kunya,death_year_hijri,reliability_grade,biography',
            'commentaries' => fn ($q) => $q
                ->select(['id', 'hadith_id', 'scholar_id', 'language_code', 'author', 'commentary_text', 'verified'])
                ->orderBy('id')
                ->limit(20),
            'commentaries.scholar:id,name_ar,name_en',
            'gradings' => fn ($q) => $q
                ->select(['id', 'hadith_id', 'scholar_id', 'grading_level_id', 'grading_notes'])
                ->orderBy('id')
                ->limit(20),
            'gradings.scholar:id,name_ar,name_en',
            'gradings.gradingLevel:id,grade_ar,grade_en,rank',
            'themes:id,theme_ar,theme_en',
        ];
    }
}
