<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\BulkOperation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Bulk Operation Repository
 */
class BulkOperationRepository extends BaseRepository implements BulkOperationRepositoryInterface
{
    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new BulkOperation;
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('created_by', $userId)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByModule(string $module, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('module', $module)
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getPending(): Collection
    {
        return $this->model
            ->where('status', 'pending')
            ->with(['creator'])
            ->orderBy('created_at', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getProcessing(): Collection
    {
        return $this->model
            ->where('status', 'processing')
            ->with(['creator'])
            ->orderBy('started_at', 'asc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with(['creator'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->model->with(['creator']);

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (isset($filters['module'])) {
            $query->where('module', $filters['module']);
        }

        if (isset($filters['operation_type'])) {
            $query->where('operation_type', $filters['operation_type']);
        }

        if (isset($filters['created_by'])) {
            $query->where('created_by', $filters['created_by']);
        }

        if (isset($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('created_at', 'desc')->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function cleanupOld(int $daysOld = 30): int
    {
        return $this->model
            ->whereIn('status', ['completed', 'failed', 'cancelled'])
            ->where('created_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
