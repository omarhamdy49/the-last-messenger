<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Helpers\FullTextSearchHelper;
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
     * Get model instance
     */
    protected function getModel(): Model
    {
        return new Sahabi;
    }

    /**
     * Get companions by status
     */
    public function getByStatus(CompanionStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->byStatus($status)
            ->byImportance()
            ->with(['translations', 'article'])
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
            ->with(['translations', 'article'])
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
            ->with(['translations', 'article'])
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
                ->with(['translations', 'article'])
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
                ->with(['translations', 'article'])
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
                ->with(['translations', 'article'])
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
            ->with(['translations', 'article'])
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
