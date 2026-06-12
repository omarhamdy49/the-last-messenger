<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Audit Log Repository
 */
class AuditLogRepository extends BaseRepository implements AuditLogRepositoryInterface
{
    protected int $cacheTTL = 300; // 5 minutes

    /**
     * Get model instance.
     */
    protected function getModel(): Model
    {
        return new AuditLog;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsForModel(Model $model, int $perPage = 15): LengthAwarePaginator
    {
        return AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
            ->where('subject_type', get_class($model))
            ->where('subject_id', $model->id)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsForUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
            ->where('user_id', $userId)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByAction(string $action, int $perPage = 15): LengthAwarePaginator
    {
        return AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
            ->where('action', $action)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsInRange(
        $startDate,
        $endDate,
        ?string $action = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($action) {
            $query->where('action', $action);
        }

        return $query->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getRecentLogs(int $limit = 50): Collection
    {
        return Cache::remember("audit_logs:recent:{$limit}", $this->cacheTTL, function () use ($limit) {
            return AuditLog::query()
                ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
                ->with('user:id,name,email')
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * {@inheritdoc}
     */
    public function getUserActivityReport(int $userId, $startDate, $endDate): array
    {
        $logs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'created_at'])
            ->where('user_id', $userId)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();

        return [
            'user_id' => $userId,
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_actions' => $logs->count(),
            'actions_by_type' => $logs->groupBy('action')->map->count()->toArray(),
            'daily_activity' => $logs->groupBy(fn ($log) => $log->created_at->format('Y-m-d'))
                ->map->count()
                ->toArray(),
            'most_active_days' => $logs->groupBy(fn ($log) => $log->created_at->format('l'))
                ->map->count()
                ->sortDesc()
                ->toArray(),
            'most_active_hours' => $logs->groupBy(fn ($log) => $log->created_at->format('H'))
                ->map->count()
                ->sortDesc()
                ->toArray(),
            'recent_actions' => $logs->take(10)->map(fn ($log) => [
                'action' => $log->action,
                'description' => $log->description,
                'created_at' => $log->created_at->diffForHumans(),
            ])->toArray(),
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function getActivityTimeline($startDate, $endDate): array
    {
        $logs = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'description', 'created_at'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();

        return $logs->groupBy(fn ($log) => $log->created_at->format('Y-m-d'))
            ->map(fn ($dayLogs) => [
                'date' => $dayLogs->first()->created_at->format('Y-m-d'),
                'count' => $dayLogs->count(),
                'actions' => $dayLogs->groupBy('action')->map->count()->toArray(),
                'events' => $dayLogs->map(fn ($log) => [
                    'id' => $log->id,
                    'action' => $log->action,
                    'description' => $log->description,
                    'user' => $log->user?->name ?? 'System',
                    'time' => $log->created_at->format('H:i:s'),
                ])->toArray(),
            ])
            ->values()
            ->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function getAuditStats(): array
    {
        return Cache::remember('audit_stats', $this->cacheTTL, function () {
            return [
                'total_logs' => AuditLog::count(),
                'logs_today' => AuditLog::whereDate('created_at', today())->count(),
                'logs_this_week' => AuditLog::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'logs_this_month' => AuditLog::whereMonth('created_at', now()->month)->count(),
                'by_action' => AuditLog::select('action', DB::raw('count(*) as count'))
                    ->groupBy('action')
                    ->orderBy('count', 'desc')
                    ->pluck('count', 'action')
                    ->toArray(),
                'by_subject_type' => AuditLog::select('subject_type', DB::raw('count(*) as count'))
                    ->groupBy('subject_type')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->pluck('count', 'subject_type')
                    ->map(fn ($count, $type) => [
                        'type' => class_basename($type),
                        'count' => $count,
                    ])
                    ->values()
                    ->toArray(),
                'most_active_users' => AuditLog::select('user_id', DB::raw('count(*) as count'))
                    ->whereNotNull('user_id')
                    ->groupBy('user_id')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->with('user:id,name,email')
                    ->get()
                    ->map(fn ($log) => [
                        'user' => $log->user?->name ?? 'Unknown',
                        'count' => $log->count,
                    ])
                    ->toArray(),
                'recent_logs' => AuditLog::query()
                    ->select(['id', 'user_id', 'action', 'description', 'created_at'])
                    ->with('user:id,name,email')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(fn ($log) => [
                        'id' => $log->id,
                        'action' => $log->action,
                        'description' => $log->description,
                        'user' => $log->user?->name ?? 'System',
                        'created_at' => $log->created_at->diffForHumans(),
                    ])
                    ->toArray(),
            ];
        });
    }

    /**
     * {@inheritdoc}
     */
    public function searchLogs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        $query = AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at']);

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['subject_type'])) {
            $query->where('subject_type', $filters['subject_type']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $query->whereBetween('created_at', [$filters['start_date'], $filters['end_date']]);
        }

        if (isset($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('description', 'like', '%'.$filters['search'].'%')
                    ->orWhere('action', 'like', '%'.$filters['search'].'%');
            });
        }

        return $query->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function pruneOldLogs(int $daysToKeep = 90): int
    {
        $cutoffDate = now()->subDays($daysToKeep);

        $deleted = AuditLog::where('created_at', '<', $cutoffDate)->delete();

        $this->clearCache();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getLogsByIp(string $ipAddress, int $perPage = 15): LengthAwarePaginator
    {
        return AuditLog::query()
            ->select(['id', 'user_id', 'action', 'subject_type', 'subject_id', 'description', 'ip_address', 'url', 'method', 'created_at'])
            ->where('ip_address', $ipAddress)
            ->with('user:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }
}
