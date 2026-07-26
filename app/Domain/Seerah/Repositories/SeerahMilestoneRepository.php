<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Models\SeerahMilestone;
use App\Domain\Seerah\Services\SeerahCacheKeys;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SeerahMilestoneRepository extends BaseRepository implements SeerahMilestoneRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SeerahMilestone;
    }

    /**
     * {@inheritdoc}
     */
    public function allActiveOrdered(): Collection
    {
        return Cache::remember(
            $this->getCacheKey('allActiveOrdered'),
            $this->cacheTTL,
            fn () => $this->model->newQuery()->with('translations')->active()->ordered()->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getActiveWithPublishedEvents(): Collection
    {
        return Cache::remember(
            SeerahCacheKeys::journeySource(),
            $this->cacheTTL,
            fn () => $this->model->newQuery()
                ->with([
                    'translations',
                    'events' => fn ($q) => $q->published()->chronological()->with([
                        'translations:id,seerah_event_id,locale,title',
                        'type:id,slug,icon,color,gradient',
                        'type.translations:id,seerah_event_type_id,locale,name',
                    ]),
                ])
                ->active()
                ->ordered()
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->newQuery()->with('translations');

        if (! empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('slug', 'LIKE', "%{$search}%")
                    ->orWhereHas('translations', fn ($tq) => $tq->where('title', 'LIKE', "%{$search}%"));
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
        /** @var SeerahMilestone $milestone */
        $milestone = $this->model->newQuery()->findOrFail($id);
        $milestone->is_active = ! $milestone->is_active;
        $milestone->save();

        $this->clearCache();

        return $milestone->is_active;
    }

    /**
     * {@inheritdoc}
     */
    public function countEvents(int $id): int
    {
        return DB::table('seerah_events')->where('seerah_milestone_id', $id)->count();
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
