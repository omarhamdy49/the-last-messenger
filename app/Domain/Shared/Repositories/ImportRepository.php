<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\Import;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Import Repository
 */
class ImportRepository extends BaseRepository implements ImportRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new Import;
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('user_id', $userId)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByModule(string $module, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('module', $module)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->where('status', $status)
            ->with('user')
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecent(int $limit = 10): Collection
    {
        return $this->model
            ->with('user')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getWithErrors(int $id): ?Import
    {
        return $this->model
            ->with(['user', 'errors', 'mappings'])
            ->find($id);
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOldCompleted(int $daysOld = 30): int
    {
        return $this->model
            ->where('status', 'completed')
            ->where('completed_at', '<', now()->subDays($daysOld))
            ->delete();
    }
}
