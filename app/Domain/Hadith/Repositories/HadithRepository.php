<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Repositories;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithIsnad;
use App\Domain\Hadith\Models\HadithTranslation;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HadithRepository extends BaseRepository implements HadithRepositoryInterface
{
    private const CACHE_TTL = 1800;

    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {
        parent::__construct();
    }

    protected function getModel(): Model
    {
        return new Hadith;
    }

    public function getByBookAndNumber(int $bookId, string $hadithNumber): ?Hadith
    {
        return Cache::remember(
            "hadith_book_{$bookId}_number_{$hadithNumber}",
            self::CACHE_TTL,
            fn () => $this->publicHadiths($this->model->newQuery())
                ->byBook($bookId)
                ->byNumber($hadithNumber)
                ->with(['translations', 'gradings', 'chapter.book.collection'])
                ->first()
        );
    }

    public function getByBook(int $bookId): Collection
    {
        return Cache::remember(
            "hadiths_by_book_{$bookId}",
            self::CACHE_TTL,
            fn () => $this->baseListQuery()->byBook($bookId)->get()
        );
    }

    public function getByGrading(string $gradingStatus): Collection
    {
        return $this->baseListQuery()->byGrading($gradingStatus)->get();
    }

    public function getAuthentic(): Collection
    {
        return Cache::remember(
            'hadiths_authentic',
            self::CACHE_TTL,
            fn () => $this->baseListQuery()->authentic()->get()
        );
    }

    public function getWithAgreement(): Collection
    {
        return $this->baseListQuery()->withAgreement()->get();
    }

    public function getByTheme(int $themeId): Collection
    {
        return $this->baseListQuery()
            ->whereHas('themes', fn (Builder $theme) => $theme->whereKey($themeId))
            ->with('themes')
            ->get();
    }

    public function getByNarrator(int $narratorId): Collection
    {
        return $this->baseListQuery()
            ->where(function (Builder $query) use ($narratorId): void {
                $query->where('primary_narrator_id', $narratorId)
                    ->orWhereHas('isnads', fn (Builder $isnad) => $isnad->where('narrator_id', $narratorId));
            })
            ->with(['primaryNarrator', 'isnads.narrator'])
            ->get();
    }

    public function search(string $query, int $perPage = 15, array $filters = []): LengthAwarePaginator
    {
        $builder = $this->baseListQuery();

        $builder->whereHas('translations', function (Builder $translation) use ($query): void {
            $translation->where(function (Builder $textQuery) use ($query): void {
                FullTextSearchHelper::apply($textQuery, ['text'], $query);
                $textQuery->orWhere('search_text', 'like', "%{$query}%");
            });
        });

        return $this->applyFilters($builder, $filters)->paginate($perPage);
    }

    public function paginateWithFilters(
        int $perPage = 15,
        string $search = '',
        string $sortBy = 'hadith_number',
        array $filters = []
    ): LengthAwarePaginator {
        $query = $this->baseListQuery();

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('hadith_number', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn (Builder $translation) => $translation
                        ->where('text', 'like', "%{$search}%")
                        ->orWhere('search_text', 'like', "%{$search}%"));
            });
        }

        $query = $this->applyFilters($query, $filters);
        $query->reorder();

        if ($sortBy === 'created_at') {
            $query->orderBy('created_at');
        } else {
            $query->orderByRaw('LENGTH(hadith_number)')->orderBy('hadith_number');
        }

        return $query->paginate($perPage);
    }

    public function countByGrading(string $gradingStatus): int
    {
        return Cache::remember(
            "hadiths_count_grading_{$gradingStatus}",
            self::CACHE_TTL,
            fn () => $this->model->newQuery()->byGrading($gradingStatus)->count()
        );
    }

    public function getRandom(?string $gradingStatus = null): ?Hadith
    {
        $query = $this->baseListQuery()->reorder();

        if ($gradingStatus !== null) {
            $query->byGrading($gradingStatus);
        }

        return $query->inRandomOrder()->first();
    }

    public function getWithFullRelations(int $id): ?Hadith
    {
        return $this->model->newQuery()->withFullRelations()->find($id);
    }

    public function listForAdmin(
        int $perPage = 20,
        string $search = '',
        array $filters = []
    ): LengthAwarePaginator {
        return $this->paginateWithFilters($perPage, $search, 'hadith_number', $filters);
    }

    public function searchForAdmin(
        string $search = '',
        array $filters = [],
        int $perPage = 15,
        string $locale = 'ar'
    ): LengthAwarePaginator {
        $query = $this->model->newQuery()
            ->with([
                'chapter.book.collection',
                'gradings',
                'translations' => fn ($translation) => $translation
                    ->whereIn('locale', array_unique([$locale, 'ar']))
                    ->orderBy('locale'),
            ]);

        if ($search !== '') {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('hadith_number', 'like', "%{$search}%")
                    ->orWhereHas('translations', fn (Builder $translation) => $translation
                        ->where('text', 'like', "%{$search}%")
                        ->orWhere('search_text', 'like', "%{$search}%"))
                    ->orWhereHas('chapter.book', fn (Builder $book) => $book
                        ->where('title->ar', 'like', "%{$search}%")
                        ->orWhere('title->en', 'like', "%{$search}%"));
            });
        }

        return $this->applyFilters($query, $filters)
            ->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number')
            ->paginate($perPage);
    }

    public function bulkDelete(array $ids): int
    {
        $count = $this->model->newQuery()->whereIn('id', $ids)->delete();
        $this->clearCache();

        return (int) $count;
    }

    public function paginatePublicByBook(
        int $bookId,
        int $perPage = 15,
        int $page = 1,
        ?string $gradingStatus = null,
        string $search = '',
        string $locale = 'ar'
    ): LengthAwarePaginator {
        $query = $this->publicHadiths($this->model->newQuery())
            ->byBook($bookId)
            ->with([
                'chapter.book.collection',
                'gradings',
                'themes',
                'translations' => fn ($translation) => $translation
                    ->whereIn('locale', array_unique([$locale, 'ar'])),
            ]);

        if ($gradingStatus !== null && $gradingStatus !== '') {
            $query->byGrading($gradingStatus);
        }

        if ($search !== '') {
            $query->whereHas('translations', fn (Builder $translation) => $translation
                ->where('text', 'like', '%'.$search.'%')
                ->orWhere('search_text', 'like', '%'.$search.'%'));
        }

        return $query->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function getWithPublicDetail(int $id): ?Hadith
    {
        return $this->publicHadiths($this->model->newQuery())
            ->with($this->publicDetailRelations())
            ->find($id);
    }

    public function getByTextHash(string $textHash, int $excludeHadithId): Collection
    {
        return $this->publicHadiths($this->model->newQuery())
            ->where('text_hash', $textHash)
            ->whereKeyNot($excludeHadithId)
            ->with($this->publicDetailRelations())
            ->orderBy('id')
            ->limit(12)
            ->get();
    }

    public function countByTextHash(string $textHash, int $excludeHadithId): int
    {
        return $this->publicHadiths($this->model->newQuery())
            ->where('text_hash', $textHash)
            ->whereKeyNot($excludeHadithId)
            ->count();
    }

    public function hadithIdsWithTranslation(array $hadithIds, string $languageCode): array
    {
        if ($hadithIds === []) {
            return [];
        }

        return HadithTranslation::query()
            ->whereIn('hadith_id', $hadithIds)
            ->whereHas('hadith', fn (Builder $query) => $this->publicHadiths($query))
            ->where('locale', $languageCode)
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->distinct()
            ->pluck('hadith_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();
    }

    public function translationsForHadiths(array $hadithIds, string $languageCode): array
    {
        if ($hadithIds === []) {
            return [];
        }

        return HadithTranslation::query()
            ->select(['hadith_id', 'text'])
            ->whereIn('hadith_id', $hadithIds)
            ->whereHas('hadith', fn (Builder $query) => $this->publicHadiths($query))
            ->where('locale', $languageCode)
            ->whereNotNull('text')
            ->where('text', '!=', '')
            ->get()
            ->mapWithKeys(static fn (HadithTranslation $translation): array => [
                (int) $translation->hadith_id => (string) $translation->text,
            ])
            ->all();
    }

    public function addNarratorToChain(
        int $hadithId,
        int $narratorId,
        ?string $transmissionMethod
    ): HadithIsnad {
        $link = DB::transaction(function () use ($hadithId, $narratorId, $transmissionMethod): HadithIsnad {
            $nextPosition = 1 + (int) (HadithIsnad::query()
                ->where('hadith_id', $hadithId)
                ->max('position_in_chain') ?? 0);

            return HadithIsnad::query()->create([
                'hadith_id' => $hadithId,
                'narrator_id' => $narratorId,
                'position_in_chain' => $nextPosition,
                'transmission_method' => $transmissionMethod,
            ]);
        });

        $this->clearCache();

        return $link;
    }

    public function moveIsnadLink(int $hadithId, int $isnadId, string $direction): bool
    {
        $moved = DB::transaction(function () use ($hadithId, $isnadId, $direction): bool {
            $link = HadithIsnad::query()->where('hadith_id', $hadithId)->findOrFail($isnadId);
            $neighbor = $direction === 'up'
                ? $link->getPreviousNarrator()
                : $link->getNextNarrator();

            if ($neighbor === null) {
                return false;
            }

            $linkPosition = $link->position_in_chain;
            $neighborPosition = $neighbor->position_in_chain;
            $temporaryPosition = 1 + (int) HadithIsnad::query()
                ->where('hadith_id', $hadithId)
                ->max('position_in_chain');

            $link->update(['position_in_chain' => $temporaryPosition]);
            $neighbor->update(['position_in_chain' => $linkPosition]);
            $link->update(['position_in_chain' => $neighborPosition]);

            return true;
        });

        if ($moved) {
            $this->clearCache();
        }

        return $moved;
    }

    public function removeNarratorFromChain(int $hadithId, int $isnadId): void
    {
        DB::transaction(function () use ($hadithId, $isnadId): void {
            HadithIsnad::query()->where('hadith_id', $hadithId)->whereKey($isnadId)->delete();

            HadithIsnad::query()
                ->where('hadith_id', $hadithId)
                ->orderBy('position_in_chain')
                ->get()
                ->each(function (HadithIsnad $link, int $index): void {
                    $position = $index + 1;
                    if ($link->position_in_chain !== $position) {
                        $link->update(['position_in_chain' => $position]);
                    }
                });
        });

        $this->clearCache();
    }

    private function baseListQuery(): Builder
    {
        return $this->publicHadiths($this->model->newQuery())
            ->with(['chapter.book.collection', 'translations', 'gradings'])
            ->orderByRaw('LENGTH(hadith_number)')
            ->orderBy('hadith_number');
    }

    private function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['book_id'])) {
            $query->byBook((int) $filters['book_id']);
        }

        if (! empty($filters['collection_id'])) {
            $query->whereHas('chapter.book', fn (Builder $book) => $book
                ->where('collection_id', $filters['collection_id']));
        }

        if (! empty($filters['grading_status'])) {
            $query->byGrading((string) $filters['grading_status']);
        }

        if (! empty($filters['grading_agreement'])) {
            $query->withAgreement();
        }

        if (! empty($filters['theme_ids'])) {
            $query->whereHas('themes', fn (Builder $theme) => $theme
                ->whereIn('hadith_themes.id', $filters['theme_ids']));
        }

        if (! empty($filters['narrator_id'])) {
            $narratorId = (int) $filters['narrator_id'];
            $query->where(function (Builder $query) use ($narratorId): void {
                $query->where('primary_narrator_id', $narratorId)
                    ->orWhereHas('isnads', fn (Builder $isnad) => $isnad
                        ->where('narrator_id', $narratorId));
            });
        }

        return $query;
    }

    private function publicDetailRelations(): array
    {
        return [
            'chapter.book.collection',
            'translations' => fn ($translation) => $translation->orderBy('locale'),
            'primaryNarrator.sahabi.translations',
            'isnads' => fn ($isnad) => $isnad->orderBy('position_in_chain')->limit(30),
            'isnads.narrator.sahabi.translations',
            'commentaries' => fn ($commentary) => $commentary->orderBy('id')->limit(20),
            'commentaries.scholar',
            'gradings' => fn ($grading) => $grading->orderBy('id')->limit(20),
            'gradings.scholar',
            'themes',
            'sources',
            'keywords',
            'relatedVerses',
        ];
    }

    private function publicHadiths(Builder $query): Builder
    {
        return $this->publicVisibility->apply($query, app()->getLocale());
    }
}
