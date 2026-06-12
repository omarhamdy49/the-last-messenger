<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Enums\ScheduleAction;
use App\Domain\Shared\Enums\ScheduleStatus;
use App\Domain\Shared\Models\ContentSchedule;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class SchedulerRepository extends BaseRepository implements SchedulerRepositoryInterface
{
    protected int $cacheTTL = 300; // 5 minutes cache

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new ContentSchedule;
    }

    /**
     * {@inheritdoc}
     */
    public function getDueSchedules(): Collection
    {
        return $this->model
            ->with(['schedulable', 'creator'])
            ->due()
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(ScheduleStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['schedulable', 'creator'])
            ->byStatus($status)
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByAction(ScheduleAction|string $action, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['schedulable', 'creator'])
            ->byAction($action)
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getSchedulesForContent(string $type, int $id): Collection
    {
        $cacheKey = "schedules:content:{$type}:{$id}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($type, $id) {
            return $this->model
                ->with(['creator'])
                ->where('schedulable_type', $type)
                ->where('schedulable_id', $id)
                ->whereIn('status', [ScheduleStatus::PENDING, ScheduleStatus::PROCESSING])
                ->orderBy('scheduled_at', 'asc')
                ->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getHistoryForContent(string $type, int $id, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['creator'])
            ->where('schedulable_type', $type)
            ->where('schedulable_id', $id)
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getUpcoming(int $limit = 10): Collection
    {
        $cacheKey = "schedules:upcoming:{$limit}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($limit) {
            return $this->model
                ->with(['schedulable', 'creator'])
                ->pending()
                ->where('scheduled_at', '>', now())
                ->orderBy('scheduled_at', 'asc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getRecurring(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['schedulable', 'creator'])
            ->recurring()
            ->orderBy('scheduled_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getRetryable(): Collection
    {
        return $this->model
            ->with(['schedulable', 'creator'])
            ->retryable()
            ->orderBy('scheduled_at', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function hasPendingSchedule(string $type, int $id, ScheduleAction $action): bool
    {
        return $this->model
            ->where('schedulable_type', $type)
            ->where('schedulable_id', $id)
            ->where('action', $action)
            ->where('status', ScheduleStatus::PENDING)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function createSchedule(array $data): ContentSchedule
    {
        $schedule = $this->model->create($data);
        $this->clearCache();

        return $schedule->fresh(['schedulable', 'creator']);
    }

    /**
     * {@inheritdoc}
     */
    public function updateSchedule(int $id, array $data): bool
    {
        $schedule = $this->findOrFail($id);

        if (! $schedule->canBeUpdated()) {
            return false;
        }

        $updated = $schedule->update($data);

        if ($updated) {
            $this->clearCache();
        }

        return $updated;
    }

    /**
     * {@inheritdoc}
     */
    public function cancelSchedule(int $id): bool
    {
        $schedule = $this->findOrFail($id);

        if (! $schedule->canBeCancelled()) {
            return false;
        }

        $schedule->markAsCancelled();
        $this->clearCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsProcessing(int $id): bool
    {
        $schedule = $this->findOrFail($id);
        $schedule->markAsProcessing();
        $this->clearCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsPublished(int $id): bool
    {
        $schedule = $this->findOrFail($id);
        $schedule->markAsPublished();
        $this->clearCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function markAsFailed(int $id, string $errorMessage): bool
    {
        $schedule = $this->findOrFail($id);
        $schedule->markAsFailed($errorMessage);
        $this->clearCache();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getStatistics(): array
    {
        $cacheKey = 'schedules:statistics';

        return Cache::remember($cacheKey, 300, function () {
            return [
                'total' => $this->model->count(),
                'pending' => $this->model->where('status', ScheduleStatus::PENDING)->count(),
                'processing' => $this->model->where('status', ScheduleStatus::PROCESSING)->count(),
                'published' => $this->model->where('status', ScheduleStatus::PUBLISHED)->count(),
                'failed' => $this->model->where('status', ScheduleStatus::FAILED)->count(),
                'cancelled' => $this->model->where('status', ScheduleStatus::CANCELLED)->count(),
                'due_today' => $this->model
                    ->pending()
                    ->whereDate('scheduled_at', today())
                    ->count(),
                'upcoming' => $this->model
                    ->pending()
                    ->where('scheduled_at', '>', now())
                    ->count(),
                'recurring' => $this->model->recurring()->count(),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->with(['schedulable', 'creator']);

        // Filter by status
        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by action
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        // Filter by content type
        if (! empty($filters['type'])) {
            $query->where('schedulable_type', $filters['type']);
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->whereDate('scheduled_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('scheduled_at', '<=', $filters['date_to']);
        }

        // Filter by creator
        if (! empty($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        // Filter recurring only
        if (! empty($filters['recurring'])) {
            $query->recurring();
        }

        $perPage = $filters['per_page'] ?? 15;

        return $query->orderBy('scheduled_at', 'desc')->paginate($perPage);
    }
}
