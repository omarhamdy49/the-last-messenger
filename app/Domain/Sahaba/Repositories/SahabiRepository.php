<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\DTOs\SahabiListFilters;
use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Sahaba\Models\SahabiCategory;
use App\Domain\Sahaba\Models\SahabiTranslation;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Shared\Services\PublicVisibilityRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SahabiRepository extends BaseRepository implements SahabiRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * Translation columns the cards/listings actually render. Deliberately
     * excludes the heavy longtext fields (biography, virtues, famous_narrations,
     * martyrdom) so list queries never haul body text the cards don't use. The
     * FK + locale are required for the relation to hydrate and resolve locales.
     */
    private const LIST_TRANSLATION_COLUMNS = ['id', 'sahabi_id', 'locale', 'name', 'full_name', 'kunya', 'laqab', 'is_ready'];

    public function __construct(
        private readonly PublicVisibilityRegistry $publicVisibility,
    ) {
        parent::__construct();
    }

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Sahabi;
    }

    public function find(int $id, array $columns = ['*'], array $relations = []): ?Model
    {
        return $this->model
            ->newQuery()
            ->onlySahaba()
            ->select($columns)
            ->with($relations)
            ->find($id);
    }

    public function findOrFail(int $id, array $columns = ['*'], array $relations = []): Model
    {
        return $this->model
            ->newQuery()
            ->onlySahaba()
            ->select($columns)
            ->with($relations)
            ->findOrFail($id);
    }

    /**
     * Eager-load set for companion listings and cards: lightweight translations
     * (names only, no body text) plus the related article.
     *
     * @return array<int|string, mixed>
     */
    private function listingRelations(): array
    {
        return [
            'translations' => fn ($query) => $query->select(self::LIST_TRANSLATION_COLUMNS),
            'categories',
        ];
    }

    /**
     * Get companions by historical-rank category (badr, ansar, muhajir, ...)
     */
    public function getByCategory(CompanionStatus|string $category, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicSahaba($this->model->newQuery())
            ->inCategory($category)
            ->byImportance()
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Replace this Sahabi's category memberships with the given set of keys.
     *
     * @param  array<int, string>  $categoryKeys
     */
    public function syncCategories(Sahabi $sahabi, array $categoryKeys): void
    {
        $categoryIds = SahabiCategory::whereIn('key', $categoryKeys)->pluck('id');
        $sahabi->categories()->sync($categoryIds);
        $this->clearCache();
    }

    /**
     * Get companions by tribe
     */
    public function getByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicSahaba($this->model->newQuery())
            ->byTribe($tribe)
            ->byImportance()
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Get companions who participated in a battle.
     *
     * Uses a JOIN against sahabi_battles (battle_name, sahabi_id composite
     * index) instead of the previous whereJsonContains full-table scan.
     *
     * Execution plan (before): type=ALL, Extra=Using where (JSON function)
     * Execution plan (after):  type=ref on sahabi_battles.battle_name index,
     *                          then eq_ref lookup on sahaba.PRIMARY
     */
    public function getByBattle(string $battle, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicSahaba(Sahabi::query())
            ->select('sahaba.*')
            ->join('sahabi_battles', 'sahaba.id', '=', 'sahabi_battles.sahabi_id')
            ->where('sahabi_battles.battle_name', $battle)
            ->byImportance()
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Get featured companions
     */
    public function getFeatured(int $limit = 10): Collection
    {
        $locale = app()->getLocale();

        return Cache::remember(
            $this->getCacheKey('publicFeatured', compact('limit', 'locale')),
            self::CACHE_TTL,
            fn () => $this->publicSahaba($this->model->newQuery())
                ->featured()
                ->byImportance()
                ->with($this->listingRelations())
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get early Muslims by conversion order
     */
    public function getEarlyMuslims(int $limit = 10): Collection
    {
        $locale = app()->getLocale();

        return Cache::remember(
            $this->getCacheKey('publicEarlyMuslims', compact('limit', 'locale')),
            self::CACHE_TTL,
            fn () => $this->publicSahaba($this->model->newQuery())
                ->earlyMuslims($limit)
                ->with($this->listingRelations())
                ->get()
        );
    }

    /**
     * Get top hadith narrators
     */
    public function getTopNarrators(int $limit = 10): Collection
    {
        $locale = app()->getLocale();

        return Cache::remember(
            $this->getCacheKey('publicTopNarrators', compact('limit', 'locale')),
            self::CACHE_TTL,
            fn () => $this->publicSahaba($this->model->newQuery())
                ->topNarrators($limit)
                ->with($this->listingRelations())
                ->get()
        );
    }

    /**
     * Search companions via translation table.
     * FULLTEXT index covers (name, full_name, kunya, laqab) on sahabi_translations.
     * 'tribe' on sahaba table has no FULLTEXT index and keeps LIKE.
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->publicSahaba($this->model->newQuery())
            ->where(function ($q) use ($query) {
                $q->whereHas('translations', function ($tq) use ($query) {
                    FullTextSearchHelper::apply($tq, ['name', 'full_name', 'kunya', 'laqab'], $query);
                })
                    ->orWhere('tribe', 'LIKE', "%{$query}%");
            })
            ->byImportance()
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Join the active-locale translation row (falling back to the default
     * language) and order alphabetically by the name that is actually
     * displayed. COALESCE keeps it standard SQL so the non-MySQL test driver
     * runs it too. Shared by every listing query that displays a locale-aware
     * name column, so the join/order logic lives in one place.
     */
    private function applyLocaleNameOrder(Builder $query): Builder
    {
        $locale = app()->getLocale();
        $fallback = Language::getDefaultCode();

        return $query
            ->leftJoin('sahabi_translations as name_loc', function ($join) use ($locale) {
                $join->on('name_loc.sahabi_id', '=', 'sahaba.id')->where('name_loc.locale', $locale);
            })
            ->leftJoin('sahabi_translations as name_fb', function ($join) use ($fallback) {
                $join->on('name_fb.sahabi_id', '=', 'sahaba.id')->where('name_fb.locale', $fallback);
            })
            ->orderByRaw('COALESCE(name_loc.name, name_fb.name) ASC')
            ->select('sahaba.*');
    }

    /**
     * Paginate all companions for the public listing — locale-aware name
     * order, with lightweight translations (no body text) and the related
     * article eager loaded. Replaces the generic paginate() path that
     * triggered a per-row translations query (N+1) and pulled full body
     * text on every card.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        return $this->applyLocaleNameOrder(
            $this->publicSahaba($this->model->newQuery())
        )
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Paginate companions for the admin data table: same locale-aware name
     * order as {@see getAllPaginated()}, plus search (FULLTEXT on
     * name/full_name/kunya/laqab) and the gender/category/tribe/featured/
     * verified filters exposed by the "Advanced Filters" panel.
     */
    public function getFilteredPaginated(SahabiListFilters $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->applyLocaleNameOrder(
            $this->model->newQuery()->onlySahaba()
        );

        if ($filters->search !== null) {
            // Scoped to name/kunya/laqab only (not full_name, which holds the
            // multi-generation nasab chain — including it would match a
            // companion whose *ancestor* was named e.g. "Amr", not the
            // companion himself). Needs its own FULLTEXT index since MATCH()
            // requires the exact column list of an existing index.
            $query->whereHas('translations', function ($tq) use ($filters) {
                FullTextSearchHelper::apply($tq, ['name', 'kunya', 'laqab'], $filters->search);
            });
        }

        if ($filters->gender !== null) {
            $query->byGender($filters->gender);
        }

        if ($filters->categories !== []) {
            $query->whereHas('categories', fn ($q) => $q->whereIn('key', $filters->categories));
        }

        if ($filters->tribe !== null) {
            $query->byTribe($filters->tribe);
        }

        if ($filters->isFeatured !== null) {
            $query->where('is_featured', $filters->isFeatured);
        }

        if ($filters->isVerified !== null) {
            $query->where('is_verified', $filters->isVerified);
        }

        return $query
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Bulk set (or clear) the featured flag for the given companions.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkSetFeatured(array $ids, bool $featured): int
    {
        $affected = DB::transaction(
            fn () => $this->model->newQuery()->whereIn('id', $ids)->update(['is_featured' => $featured])
        );

        $this->clearCache();

        return $affected;
    }

    /**
     * Bulk publish/unpublish companions. `is_ready` lives per-locale on
     * sahabi_translations, but a bulk admin action treats "published" as a
     * single companion-level status — so every locale row for the selected
     * companions is updated together, not just the active admin locale.
     *
     * @param  array<int, int>  $ids
     */
    public function bulkSetReadyStatus(array $ids, bool $isReady): int
    {
        $affected = DB::transaction(
            fn () => SahabiTranslation::whereIn('sahabi_id', $ids)->update(['is_ready' => $isReady])
        );

        $this->clearCache();

        return $affected;
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $defaultRelations = ['translations', 'categories', 'father', 'mother'];
        $relations = empty($relations) ? $defaultRelations : $relations;
        $locale = app()->getLocale();

        return Cache::remember(
            $this->getCacheKey('publicSlug', compact('slug', 'locale')),
            self::CACHE_TTL,
            fn () => $this->publicSahaba($this->model->newQuery())
                ->with($relations)
                ->where('slug', $slug)
                ->first($columns)
        );
    }

    /**
     * Clear cache for companion
     */
    public function clearCacheForModel(int $id): void
    {
        $sahabi = $this->find($id);
        if ($sahabi) {
            $this->clearCache();
            Cache::forget("sahabi_slug_{$sahabi->slug}");
            Cache::forget('sahaba_featured_10');
            Cache::forget('sahaba_early_muslims_10');
            Cache::forget('sahaba_top_narrators_10');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getIndexStats(): array
    {
        $row = DB::table('sahaba')
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female
            ")
            ->whereNull('deleted_at')
            ->where('is_sahabi', true)
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'male' => (int) ($row->male ?? 0),
            'female' => (int) ($row->female ?? 0),
        ];
    }

    public function getPublicIndexStats(): array
    {
        $row = $this->publicSahaba($this->model->newQuery())
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN gender = 'male' THEN 1 ELSE 0 END) as male,
                SUM(CASE WHEN gender = 'female' THEN 1 ELSE 0 END) as female
            ")
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'male' => (int) ($row->male ?? 0),
            'female' => (int) ($row->female ?? 0),
        ];
    }

    private function publicSahaba(Builder $query): Builder
    {
        return $this->publicVisibility
            ->apply($query, app()->getLocale())
            ->onlySahaba();
    }
}
