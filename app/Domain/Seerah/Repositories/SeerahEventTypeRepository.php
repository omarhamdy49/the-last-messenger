<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahEventType;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeerahEventTypeRepository extends BaseRepository implements SeerahEventTypeRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SeerahEventType;
    }

    /**
     * {@inheritdoc}
     */
    public function allActiveOrdered(): Collection
    {
        $cacheKey = $this->getCacheKey('allActiveOrdered');

        return Cache::remember(
            $cacheKey,
            $this->cacheTTL,
            fn () => $this->model->newQuery()->active()->get()
                ->sortBy(fn (SeerahEventType $type): string => $type->name('ar'))
                ->values()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery();

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            // ponytail: LIKE over the JSON names is a table scan; fine for a lookup of ~11 rows.
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'LIKE', "%{$search}%")
                    ->orWhere('locale', 'LIKE', "%{$search}%");
            });
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            $query->where('is_active', $filters['status'] === 'active');
        }

        return $query->withCount('events')
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * {@inheritdoc}
     */
    public function bulkDelete(array $ids): int
    {
        $deleted = DB::transaction(
            fn () => $this->model->newQuery()->whereIn('id', $ids)->delete()
        );

        $this->clearCache();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function toggleActive(int $id): bool
    {
        /** @var SeerahEventType $type */
        $type = $this->model->newQuery()->findOrFail($id);
        $type->is_active = ! $type->is_active;
        $type->save();

        $this->clearCache();

        return $type->is_active;
    }

    /**
     * {@inheritdoc}
     */
    public function countEvents(int $id): int
    {
        return DB::table('seerah_events')->where('event_type', $id)->count();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        $model = parent::create($data);
        $this->clearCache();

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, array $data): bool
    {
        $updated = parent::update($id, $data);
        $this->clearCache();

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): bool
    {
        $deleted = parent::delete($id);
        $this->clearCache();

        return $deleted;
    }
}
