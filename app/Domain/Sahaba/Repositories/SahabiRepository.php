<?php

declare(strict_types=1);

namespace App\Domain\Sahaba\Repositories;

use App\Domain\Sahaba\Enums\CompanionStatus;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

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
            ->with(['article'])
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
            ->with(['article'])
            ->paginate($perPage);
    }

    /**
     * Get companions who participated in a battle
     */
    public function getByBattle(string $battle, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->participatedIn($battle)
            ->byImportance()
            ->with(['article'])
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
                ->with(['article'])
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
                ->with(['article'])
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
                ->with(['article'])
                ->get()
        );
    }

    /**
     * Search companions
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where(function ($q) use ($query) {
                $q->whereRaw("JSON_SEARCH(name, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhereRaw("JSON_SEARCH(full_name, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhereRaw("JSON_SEARCH(kunya, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhereRaw("JSON_SEARCH(laqab, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhere('tribe', 'like', "%{$query}%");
            })
            ->byImportance()
            ->with(['article'])
            ->paginate($perPage);
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $defaultRelations = ['article', 'father', 'mother'];
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
}
