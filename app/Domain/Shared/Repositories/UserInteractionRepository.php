<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Enums\InteractionType;
use App\Domain\Shared\Models\UserInteraction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * User Interaction Repository Implementation
 */
class UserInteractionRepository extends BaseRepository implements UserInteractionRepositoryInterface
{
    protected int $cacheTTL = 1800; // 30 minutes

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new UserInteraction;
    }

    /**
     * {@inheritdoc}
     */
    public function getByUser(int $userId, ?int $limit = null): Collection
    {
        $query = $this->model->where('user_id', $userId)
            ->with('interactable')
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getByContent(string $contentType, int $contentId): Collection
    {
        return Cache::remember(
            "interactions:content:{$contentType}:{$contentId}",
            $this->cacheTTL,
            fn () => $this->model
                ->where('interactable_type', $contentType)
                ->where('interactable_id', $contentId)
                ->with('user')
                ->latest()
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getByType(InteractionType|string $type, ?int $limit = null): Collection
    {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        $query = $this->model->where('interaction_type', $type)
            ->with(['user', 'interactable'])
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getBetweenDates(Carbon $startDate, Carbon $endDate): Collection
    {
        return $this->model
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with(['user', 'interactable'])
            ->latest()
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getUserContentInteractions(int $userId, string $contentType, int $contentId): Collection
    {
        return $this->model
            ->where('user_id', $userId)
            ->where('interactable_type', $contentType)
            ->where('interactable_id', $contentId)
            ->latest()
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function hasInteraction(
        int $userId,
        string $contentType,
        int $contentId,
        InteractionType|string $type
    ): bool {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return $this->model
            ->where('user_id', $userId)
            ->where('interactable_type', $contentType)
            ->where('interactable_id', $contentId)
            ->where('interaction_type', $type)
            ->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function getRecent(int $days = 7, ?int $limit = null): Collection
    {
        $query = $this->model
            ->where('created_at', '>=', now()->subDays($days))
            ->with(['user', 'interactable'])
            ->latest();

        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getToday(): Collection
    {
        return Cache::remember(
            'interactions:today:'.now()->toDateString(),
            300, // 5 minutes cache
            fn () => $this->model
                ->whereDate('created_at', today())
                ->with(['user', 'interactable'])
                ->latest()
                ->get()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getThisWeek(): Collection
    {
        return $this->model
            ->whereBetween('created_at', [
                now()->startOfWeek(),
                now()->endOfWeek(),
            ])
            ->with(['user', 'interactable'])
            ->latest()
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function getThisMonth(): Collection
    {
        return $this->model
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->with(['user', 'interactable'])
            ->latest()
            ->get();
    }

    /**
     * {@inheritdoc}
     *
     * Two aggregates instead of hydrating every interaction row for the item:
     * COUNT(DISTINCT user_id) cannot be derived from a per-type GROUP BY, so
     * it needs its own pass.
     */
    public function aggregateByContent(string $contentType, int $contentId): array
    {
        $scope = fn () => $this->model->newQuery()
            ->where('interactable_type', $contentType)
            ->where('interactable_id', $contentId);

        $byType = $scope()
            ->select('interaction_type', DB::raw('COUNT(*) as aggregate'))
            ->groupBy('interaction_type')
            ->pluck('aggregate', 'interaction_type')
            ->mapWithKeys(fn ($count, $type): array => [
                // interaction_type is enum-cast on the model, so the grouped
                // key comes back as an InteractionType, not a string.
                ($type instanceof InteractionType ? $type->value : (string) $type) => (int) $count,
            ])
            ->all();

        $totals = $scope()
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users, AVG(duration_seconds) as avg_duration')
            ->first();

        return [
            'by_type' => $byType,
            'unique_users' => (int) ($totals->unique_users ?? 0),
            'avg_duration' => $totals?->avg_duration !== null ? (float) $totals->avg_duration : null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * One grouped query for the whole window; the caller back-fills the days
     * that produced no rows.
     */
    public function dailyCountsByUser(int $userId, Carbon $startDate, Carbon $endDate): array
    {
        $rows = $this->model->newQuery()
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->selectRaw('DATE(created_at) as day, interaction_type, COUNT(*) as aggregate')
            ->groupBy(DB::raw('DATE(created_at)'), 'interaction_type')
            ->get();

        $counts = [];
        foreach ($rows as $row) {
            $type = $row->interaction_type;
            $counts[(string) $row->day][$type instanceof InteractionType ? $type->value : (string) $type]
                = (int) $row->aggregate;
        }

        return $counts;
    }

    /**
     * {@inheritdoc}
     */
    public function countBetweenDates(Carbon $startDate, Carbon $endDate): int
    {
        return $this->model->newQuery()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->count();
    }

    /**
     * {@inheritdoc}
     */
    public function countByType(InteractionType|string $type): int
    {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return Cache::remember(
            "interactions:count:type:{$type}",
            $this->cacheTTL,
            fn () => $this->model->where('interaction_type', $type)->count()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function countByUser(int $userId): int
    {
        return Cache::remember(
            "interactions:count:user:{$userId}",
            $this->cacheTTL,
            fn () => $this->model->where('user_id', $userId)->count()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getMostInteractedContent(int $limit = 10): array
    {
        return Cache::remember(
            "interactions:most_interacted:{$limit}",
            3600, // 1 hour cache
            fn () => $this->model
                ->select('interactable_type', 'interactable_id', DB::raw('COUNT(*) as interaction_count'))
                ->groupBy('interactable_type', 'interactable_id')
                ->orderByDesc('interaction_count')
                ->limit($limit)
                ->get()
                ->toArray()
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getStatsBetweenDates(Carbon $startDate, Carbon $endDate): array
    {
        $cacheKey = "interactions:stats:{$startDate->toDateString()}:{$endDate->toDateString()}";

        return Cache::remember($cacheKey, $this->cacheTTL, function () use ($startDate, $endDate) {
            $stats = $this->model
                ->whereBetween('created_at', [$startDate, $endDate])
                ->select('interaction_type', DB::raw('COUNT(*) as count'))
                ->groupBy('interaction_type')
                ->pluck('count', 'interaction_type')
                ->toArray();

            // Add total
            $stats['total'] = array_sum($stats);

            return $stats;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function deleteOlderThan(Carbon $date): int
    {
        $deleted = $this->model->where('created_at', '<', $date)->delete();
        $this->clearCache();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function createOrUpdate(array $data): UserInteraction
    {
        $interaction = $this->model
            ->where('user_id', $data['user_id'])
            ->where('interactable_type', $data['interactable_type'])
            ->where('interactable_id', $data['interactable_id'])
            ->where('interaction_type', $data['interaction_type'])
            ->whereDate('created_at', today())
            ->first();

        if ($interaction) {
            $interaction->update($data);
            $this->clearCache();

            return $interaction->fresh();
        }

        return $this->create($data);
    }
}
