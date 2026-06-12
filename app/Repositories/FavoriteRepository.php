<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Domain\Shared\Repositories\FavoriteRepositoryInterface;
use App\Models\Favorite;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * Eloquent implementation of the favorites (bookmarks) repository.
 *
 * All access is scoped by user_id, which is the ownership boundary that
 * prevents IDOR: a user can only ever read or mutate their own rows.
 */
final class FavoriteRepository implements FavoriteRepositoryInterface
{
    /**
     * Per-type nested eager loads for the polymorphic target, so rendering a
     * page of mixed bookmarks never triggers N+1 on translations/relations.
     *
     * @var array<class-string<Model>, list<string>>
     */
    private array $morphEagerLoads = [];

    /**
     * Register nested relations to eager-load for a given morph class when
     * listing favorites. Called at boot by the favorites wiring so this
     * Infrastructure repository stays free of domain-model imports.
     *
     * @param  class-string<Model>  $morphClass
     * @param  list<string>  $relations
     */
    public function eagerLoadFor(string $morphClass, array $relations): void
    {
        $this->morphEagerLoads[$morphClass] = $relations;
    }

    public function paginateForUser(int $userId, ?string $favorableType, int $perPage): LengthAwarePaginator
    {
        return Favorite::query()
            ->select(['id', 'user_id', 'favorable_id', 'favorable_type', 'created_at'])
            ->where('user_id', $userId)
            ->when(
                $favorableType !== null,
                fn ($query) => $query->where('favorable_type', $favorableType),
            )
            ->with(['favorable' => function ($morphTo): void {
                $morphTo->morphWith($this->morphEagerLoads);
            }])
            ->latest()
            ->paginate($perPage);
    }

    public function countsByTypeForUser(int $userId): array
    {
        /** @var array<string, int> $counts */
        $counts = Favorite::query()
            ->where('user_id', $userId)
            ->select('favorable_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('favorable_type')
            ->pluck('aggregate', 'favorable_type')
            ->map(static fn ($value): int => (int) $value)
            ->all();

        return $counts;
    }

    public function totalForUser(int $userId): int
    {
        return Favorite::query()->where('user_id', $userId)->count();
    }

    public function exists(int $userId, string $favorableType, int $favorableId): bool
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $favorableId)
            ->exists();
    }

    public function idsForUser(int $userId, string $favorableType): array
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->pluck('favorable_id')
            ->map(static fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    public function add(int $userId, string $favorableType, int $favorableId): bool
    {
        /** @var Favorite|null $existing */
        $existing = Favorite::query()
            ->where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $favorableId)
            ->first();

        if ($existing !== null) {
            return false;
        }

        Favorite::query()->create([
            'user_id' => $userId,
            'favorable_type' => $favorableType,
            'favorable_id' => $favorableId,
        ]);

        return true;
    }

    public function remove(int $userId, string $favorableType, int $favorableId): int
    {
        return Favorite::query()
            ->where('user_id', $userId)
            ->where('favorable_type', $favorableType)
            ->where('favorable_id', $favorableId)
            ->delete();
    }
}
