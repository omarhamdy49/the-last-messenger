<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\SavedFilter;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Saved Filter Repository Implementation
 */
class SavedFilterRepository extends BaseRepository implements SavedFilterRepositoryInterface
{
    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SavedFilter;
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, ?string $module = null): Collection
    {
        $query = $this->model
            ->where('user_id', $userId)
            ->orderBy('is_default', 'desc')
            ->orderBy('usage_count', 'desc');

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getPublic(?string $module = null): Collection
    {
        $query = $this->model
            ->where('is_public', true)
            ->orderBy('usage_count', 'desc');

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getDefault(int $userId, string $module): ?SavedFilter
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('module', $module)
            ->where('is_default', true)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function setAsDefault(int $filterId, int $userId): bool
    {
        $filter = $this->findOrFail($filterId);

        // Verify ownership
        if ($filter->user_id !== $userId) {
            return false;
        }

        // Unset other defaults for this module
        $this->model
            ->where('user_id', $userId)
            ->where('module', $filter->module)
            ->where('is_default', true)
            ->update(['is_default' => false]);

        // Set this as default
        return (bool) $filter->update(['is_default' => true]);
    }

    /**
     * {@inheritdoc}
     */
    public function incrementUsage(int $filterId): bool
    {
        $filter = $this->findOrFail($filterId);
        $filter->increment('usage_count');

        return (bool) $filter->update(['last_used_at' => now()]);
    }

    /**
     * {@inheritdoc}
     */
    public function getMostUsed(int $userId, int $limit = 10): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderByDesc('usage_count')
            ->limit($limit)
            ->get();
    }
}
