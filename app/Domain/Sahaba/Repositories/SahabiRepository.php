<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Repositories\BaseRepository;
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
    private const LIST_TRANSLATION_COLUMNS = ['id', 'sahabi_id', 'locale', 'name', 'full_name', 'kunya', 'laqab'];

    /**
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Sahabi;
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
            'article',
        ];
    }

    /**
     * Get companions by status
     */
    public function getByStatus(CompanionStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byStatus($status)
            ->byImportance()
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Get companions by tribe
     */
    public function getByTribe(string $tribe, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
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
        return Sahabi::query()
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
        return Cache::remember(
            "sahaba_featured_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
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
        return Cache::remember(
            "sahaba_early_muslims_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
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
        return Cache::remember(
            "sahaba_top_narrators_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
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
        return $this->model
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
     * Paginate all companions for the public listing — importance-ordered, with
     * lightweight translations (no body text) and the related article eager
     * loaded. Replaces the generic paginate() path that triggered a per-row
     * translations query (N+1) and pulled full body text on every card.
     */
    public function getAllPaginated(int $perPage = 15): LengthAwarePaginator
    {
        $locale = app()->getLocale();
        $fallback = Language::getDefaultCode();

        // Name lives in sahabi_translations, so join the active-locale row (with
        // the default language as fallback) and sort alphabetically by the name
        // that is actually displayed. COALESCE keeps it standard SQL so the
        // non-MySQL test driver runs it too.
        return $this->model
            ->newQuery()
            ->leftJoin('sahabi_translations as name_loc', function ($join) use ($locale) {
                $join->on('name_loc.sahabi_id', '=', 'sahaba.id')->where('name_loc.locale', $locale);
            })
            ->leftJoin('sahabi_translations as name_fb', function ($join) use ($fallback) {
                $join->on('name_fb.sahabi_id', '=', 'sahaba.id')->where('name_fb.locale', $fallback);
            })
            ->orderByRaw('COALESCE(name_loc.name, name_fb.name) ASC')
            ->select('sahaba.*')
            ->with($this->listingRelations())
            ->paginate($perPage);
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $defaultRelations = ['translations', 'article', 'father', 'mother'];
        $relations = empty($relations) ? $defaultRelations : $relations;

        return Cache::remember(
            "sahabi_slug_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
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
            ->first();

        return [
            'total' => (int) ($row->total ?? 0),
            'male' => (int) ($row->male ?? 0),
            'female' => (int) ($row->female ?? 0),
        ];
    }
}
