<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\SearchHistory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Search History Repository Implementation
 */
class SearchHistoryRepository extends BaseRepository implements SearchHistoryRepositoryInterface
{
    protected int $cacheTTL = 300; // 5 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new SearchHistory;
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentByUser(int $userId, int $limit = 20): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->orderBy('searched_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getPopular(?string $module = null, int $days = 7, int $limit = 20): Collection
    {
        $query = $this->model
            ->select('query', 'module', DB::raw('COUNT(*) as search_count'))
            ->where('searched_at', '>=', now()->subDays($days))
            ->groupBy('query', 'module')
            ->orderByDesc('search_count')
            ->limit($limit);

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getSuggestions(string $query, ?string $module = null, int $limit = 10): Collection
    {
        $queryBuilder = $this->model
            ->select('query')
            ->where('query', 'LIKE', "{$query}%")
            ->groupBy('query')
            ->orderByRaw('COUNT(*) DESC')
            ->limit($limit);

        if ($module !== null) {
            $queryBuilder->where('module', $module);
        }

        return $queryBuilder->pluck('query');
    }

    /**
     * {@inheritdoc}
     */
    public function clearByUser(int $userId, ?string $module = null): int
    {
        $query = $this->model->where('user_id', $userId);

        if ($module !== null) {
            $query->where('module', $module);
        }

        return $query->delete();
    }
}
