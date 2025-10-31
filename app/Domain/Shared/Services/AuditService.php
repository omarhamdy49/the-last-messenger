<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\AuditLog;
use App\Domain\Shared\Repositories\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * Audit Service
 *
 * Handles all audit logging operations
 */
class AuditService
{
    public function __construct(
        protected AuditLogRepositoryInterface $repository
    ) {}

    /**
     * Get paginated audit logs.
     */
    public function paginate(int $perPage = 15)
    {
        return $this->repository->paginate($perPage);
    }

    /**
     * Find audit log by ID.
     */
    public function find(int $id): ?AuditLog
    {
        return $this->repository->find($id);
    }

    /**
     * Log an action.
     *
     * @param  array<string, mixed>|null  $oldValues
     * @param  array<string, mixed>|null  $newValues
     */
    public function logAction(
        ?int $userId,
        string $action,
        Model $subject,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        try {
            $auditLog = new AuditLog([
                'user_id' => $userId ?? Auth::id(),
                'action' => $action,
                'subject_type' => get_class($subject),
                'subject_id' => $subject->id,
                'description' => $description ?? ucfirst($action) . ' ' . class_basename($subject),
                'old_values' => $oldValues,
                'new_values' => $newValues,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
                'url' => request()?->fullUrl(),
                'method' => request()?->method(),
            ]);

            $auditLog->save();

            return $auditLog;
        } catch (\Exception $e) {
            Log::error('Failed to create audit log', [
                'action' => $action,
                'subject' => get_class($subject),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get audit log for a specific content/model.
     */
    public function getAuditLog(Model $subject, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getLogsForModel($subject, $perPage);
    }

    /**
     * Get audit trail for a user.
     */
    public function getAuditTrail(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getLogsForUser($userId, $perPage);
    }

    /**
     * Get activity report for a date range.
     *
     * @return array<string, mixed>
     */
    public function getActivityReport($startDate, $endDate): array
    {
        $timeline = $this->repository->getActivityTimeline($startDate, $endDate);

        return [
            'period' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
            'total_actions' => collect($timeline)->sum('count'),
            'timeline' => $timeline,
            'actions_by_type' => collect($timeline)
                ->flatMap(fn ($day) => $day['actions'])
                ->groupBy(fn ($count, $action) => $action)
                ->map(fn ($counts) => $counts->sum())
                ->sortDesc()
                ->toArray(),
        ];
    }

    /**
     * Generate audit report in specified format.
     */
    public function generateAuditReport(string $format = 'array', ?array $filters = null): array|string
    {
        $filters = $filters ?? [
            'start_date' => now()->subMonth(),
            'end_date' => now(),
        ];

        $logs = $this->repository->getLogsInRange(
            $filters['start_date'],
            $filters['end_date']
        )->getCollection();

        $report = [
            'report_generated_at' => now()->toIso8601String(),
            'period' => [
                'start' => $filters['start_date'],
                'end' => $filters['end_date'],
            ],
            'summary' => [
                'total_logs' => $logs->count(),
                'unique_users' => $logs->pluck('user_id')->unique()->count(),
                'unique_actions' => $logs->pluck('action')->unique()->count(),
            ],
            'logs_by_action' => $logs->groupBy('action')->map->count()->sortDesc()->toArray(),
            'logs_by_user' => $logs->groupBy('user_id')
                ->map(fn ($userLogs) => [
                    'user' => $userLogs->first()->user?->name ?? 'System',
                    'count' => $userLogs->count(),
                    'actions' => $userLogs->groupBy('action')->map->count()->toArray(),
                ])
                ->values()
                ->toArray(),
            'logs_by_day' => $logs->groupBy(fn ($log) => $log->created_at->format('Y-m-d'))
                ->map->count()
                ->toArray(),
        ];

        return match ($format) {
            'json' => json_encode($report, JSON_PRETTY_PRINT),
            'csv' => $this->convertReportToCsv($report),
            default => $report,
        };
    }

    /**
     * Get audit statistics.
     *
     * @return array<string, mixed>
     */
    public function getAuditStats(): array
    {
        return $this->repository->getAuditStats();
    }

    /**
     * Get recent audit logs.
     */
    public function getRecentLogs(int $limit = 50): Collection
    {
        return $this->repository->getRecentLogs($limit);
    }

    /**
     * Get user activity report.
     *
     * @return array<string, mixed>
     */
    public function getUserActivityReport(int $userId, $startDate, $endDate): array
    {
        return $this->repository->getUserActivityReport($userId, $startDate, $endDate);
    }

    /**
     * Search audit logs.
     *
     * @param  array<string, mixed>  $filters
     */
    public function searchLogs(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->searchLogs($filters, $perPage);
    }

    /**
     * Prune old audit logs.
     */
    public function pruneOldLogs(int $daysToKeep = 90): int
    {
        try {
            $deleted = $this->repository->pruneOldLogs($daysToKeep);

            Log::info('Pruned old audit logs', [
                'days_to_keep' => $daysToKeep,
                'deleted_count' => $deleted,
            ]);

            return $deleted;
        } catch (\Exception $e) {
            Log::error('Failed to prune audit logs', [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get logs by IP address.
     */
    public function getLogsByIp(string $ipAddress, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getLogsByIp($ipAddress, $perPage);
    }

    /**
     * Get logs by action type.
     */
    public function getLogsByAction(string $action, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getLogsByAction($action, $perPage);
    }

    /**
     * Export audit logs.
     *
     * @param  array<string, mixed>  $filters
     */
    public function exportLogs(array $filters = [], string $format = 'csv'): string
    {
        $logs = $this->repository->searchLogs($filters, 10000)->getCollection();

        return match ($format) {
            'json' => $logs->toJson(JSON_PRETTY_PRINT),
            'csv' => $this->convertLogsToCsv($logs),
            default => $logs->toJson(),
        };
    }

    /**
     * Convert report to CSV format.
     */
    protected function convertReportToCsv(array $report): string
    {
        $csv = "Audit Report\n";
        $csv .= "Generated: {$report['report_generated_at']}\n";
        $csv .= "Period: {$report['period']['start']} to {$report['period']['end']}\n\n";

        $csv .= "Summary\n";
        $csv .= "Total Logs,Unique Users,Unique Actions\n";
        $csv .= "{$report['summary']['total_logs']},{$report['summary']['unique_users']},{$report['summary']['unique_actions']}\n\n";

        $csv .= "Logs by Action\n";
        $csv .= "Action,Count\n";
        foreach ($report['logs_by_action'] as $action => $count) {
            $csv .= "{$action},{$count}\n";
        }

        return $csv;
    }

    /**
     * Convert logs to CSV format.
     */
    protected function convertLogsToCsv(Collection $logs): string
    {
        $csv = "ID,User,Action,Subject,Description,IP Address,Created At\n";

        foreach ($logs as $log) {
            $csv .= implode(',', [
                $log->id,
                $log->user?->name ?? 'System',
                $log->action,
                class_basename($log->subject_type),
                str_replace(',', ';', $log->description ?? ''),
                $log->ip_address ?? '',
                $log->created_at->toIso8601String(),
            ]) . "\n";
        }

        return $csv;
    }

    /**
     * Get activity heatmap (actions by hour and day).
     *
     * @return array<string, mixed>
     */
    public function getActivityHeatmap($startDate, $endDate): array
    {
        $logs = $this->repository->getLogsInRange($startDate, $endDate)->getCollection();

        return [
            'by_hour' => $logs->groupBy(fn ($log) => $log->created_at->format('H'))
                ->map->count()
                ->sortKeys()
                ->toArray(),
            'by_day_of_week' => $logs->groupBy(fn ($log) => $log->created_at->format('l'))
                ->map->count()
                ->toArray(),
            'by_date' => $logs->groupBy(fn ($log) => $log->created_at->format('Y-m-d'))
                ->map->count()
                ->toArray(),
        ];
    }
}
