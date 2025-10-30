<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\AdminLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AdminLogRepository implements AdminLogRepositoryInterface
{
    protected AdminLog $model;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $this->model = new AdminLog;
    }

    /**
     * Create a new admin log entry.
     *
     * @param  array<string, mixed>  $data
     */
    public function log(array $data): AdminLog
    {
        $log = $this->model->create($data);

        // Invalidate recent activity cache
        Cache::forget('admin_logs.recent_activity');

        return $log;
    }

    /**
     * Get recent activity logs.
     */
    public function getRecentActivity(int $limit = 20): Collection
    {
        return Cache::remember(
            'admin_logs.recent_activity',
            now()->addMinutes(5),
            fn () => $this->model
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get activity logs for a specific user.
     */
    public function getUserActivity(int $userId, int $limit = 50): Collection
    {
        return $this->model
            ->with('user:id,name,email')
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get history for a specific model.
     */
    public function getModelHistory(string $modelType, int $modelId): Collection
    {
        return $this->model
            ->with('user:id,name,email')
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Search logs with filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function search(array $filters): LengthAwarePaginator
    {
        $query = $this->model->with('user:id,name,email');

        if (! empty($filters['user_id'])) {
            $query->byUser((int) $filters['user_id']);
        }

        if (! empty($filters['action'])) {
            $query->byAction((string) $filters['action']);
        }

        if (! empty($filters['model_type'])) {
            $query->byModel((string) $filters['model_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['ip_address'])) {
            $query->where('ip_address', 'like', '%'.$filters['ip_address'].'%');
        }

        return $query->orderBy('created_at', 'desc')
            ->paginate($filters['per_page'] ?? 50);
    }

    /**
     * Get all logs with pagination.
     */
    public function getAll(int $perPage = 50): LengthAwarePaginator
    {
        return $this->model
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Find a log by ID.
     */
    public function findById(int $id): ?AdminLog
    {
        return $this->model
            ->with('user:id,name,email')
            ->find($id);
    }
}
