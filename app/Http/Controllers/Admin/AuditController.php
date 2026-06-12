<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\AuditService;
use App\Http\Controllers\Controller;
use App\Http\Requests\GenerateAuditReportRequest;
use App\Http\Resources\AuditLogResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

/**
 * Audit Controller
 *
 * Manages audit logs and compliance reporting
 */
class AuditController extends Controller
{
    use ApiResponse;

    public function __construct(
        protected AuditService $auditService
    ) {}

    /**
     * Get all audit logs with filters.
     *
     * GET /admin/audit
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $filters = [
                'user_id' => $request->input('user_id'),
                'action' => $request->input('action'),
                'subject_type' => $request->input('subject_type'),
                'ip_address' => $request->input('ip_address'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
                'search' => $request->input('search'),
            ];

            // Remove null values
            $filters = array_filter($filters, fn ($value) => ! is_null($value));

            $perPage = $request->integer('per_page', 15);

            if (empty($filters)) {
                $logs = $this->auditService->paginate($perPage);
            } else {
                $logs = $this->auditService->searchLogs($filters, $perPage);
            }

            return $this->paginatedResponse(
                $logs->setCollection(
                    $logs->getCollection()->map(fn ($log) => new AuditLogResource($log))
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve audit logs', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve audit logs', 500);
        }
    }

    /**
     * Get a specific audit log entry.
     *
     * GET /admin/audit/{id}
     */
    public function show(int $id): JsonResponse
    {
        try {
            $log = $this->auditService->find($id);

            if (! $log) {
                return $this->notFoundResponse('Audit log not found');
            }

            return $this->successResponse(new AuditLogResource($log));
        } catch (\Throwable $e) {
            Log::error('Failed to retrieve audit log', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve audit log', 500);
        }
    }

    /**
     * Get user activity.
     *
     * GET /admin/audit/user/{userId}
     */
    public function userActivity(Request $request, int $userId): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $logs = $this->auditService->getAuditTrail($userId, $perPage);

            return $this->paginatedResponse(
                $logs->setCollection(
                    $logs->getCollection()->map(fn ($log) => new AuditLogResource($log))
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get user activity', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve user activity', 500);
        }
    }

    /**
     * Generate audit report.
     *
     * POST /admin/audit/generate-report
     */
    public function generateReport(GenerateAuditReportRequest $request): JsonResponse
    {
        try {
            $validated = $request->validated();

            $format = $validated['format'] ?? 'array';
            $filters = [
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'user_id' => $validated['user_id'] ?? null,
                'action' => $validated['action'] ?? null,
                'subject_type' => $validated['subject_type'] ?? null,
                'ip_address' => $validated['ip_address'] ?? null,
            ];

            $report = $this->auditService->generateAuditReport($format, $filters);

            return $this->successResponse([
                'report' => $report,
                'format' => $format,
                'generated_at' => now()->toIso8601String(),
            ], 'Report generated successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to generate audit report', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to generate report', 500);
        }
    }

    /**
     * Export audit logs.
     *
     * GET /admin/audit/export
     */
    public function export(Request $request): Response|JsonResponse
    {
        try {
            $format = $request->input('format', 'csv');

            $filters = [
                'user_id' => $request->input('user_id'),
                'action' => $request->input('action'),
                'subject_type' => $request->input('subject_type'),
                'start_date' => $request->input('start_date'),
                'end_date' => $request->input('end_date'),
            ];

            // Remove null values
            $filters = array_filter($filters, fn ($value) => ! is_null($value));

            $export = $this->auditService->exportLogs($filters, $format);

            $filename = 'audit_logs_'.now()->format('Y_m_d_His').'.'.$format;

            return response($export, 200, [
                'Content-Type' => $format === 'csv' ? 'text/csv' : 'application/json',
                'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            ]);
        } catch (\Throwable $e) {
            Log::error('Failed to export audit logs', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to export audit logs', 500);
        }
    }

    /**
     * Get audit statistics.
     *
     * GET /admin/audit/stats
     */
    public function stats(): JsonResponse
    {
        try {
            $stats = $this->auditService->getAuditStats();

            return $this->successResponse($stats);
        } catch (\Throwable $e) {
            Log::error('Failed to get audit stats', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve statistics', 500);
        }
    }

    /**
     * Get activity report for a date range.
     *
     * GET /admin/audit/activity-report
     */
    public function activityReport(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonth());
            $endDate = $request->input('end_date', now());

            $report = $this->auditService->getActivityReport($startDate, $endDate);

            return $this->successResponse($report);
        } catch (\Throwable $e) {
            Log::error('Failed to get activity report', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve activity report', 500);
        }
    }

    /**
     * Get logs by IP address.
     *
     * GET /admin/audit/ip/{ipAddress}
     */
    public function logsByIp(Request $request, string $ipAddress): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $logs = $this->auditService->getLogsByIp($ipAddress, $perPage);

            return $this->paginatedResponse(
                $logs->setCollection(
                    $logs->getCollection()->map(fn ($log) => new AuditLogResource($log))
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get logs by IP', [
                'ip_address' => $ipAddress,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve logs', 500);
        }
    }

    /**
     * Get logs by action type.
     *
     * GET /admin/audit/action/{action}
     */
    public function logsByAction(Request $request, string $action): JsonResponse
    {
        try {
            $perPage = $request->integer('per_page', 15);
            $logs = $this->auditService->getLogsByAction($action, $perPage);

            return $this->paginatedResponse(
                $logs->setCollection(
                    $logs->getCollection()->map(fn ($log) => new AuditLogResource($log))
                )
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get logs by action', [
                'action' => $action,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve logs', 500);
        }
    }

    /**
     * Get activity heatmap.
     *
     * GET /admin/audit/heatmap
     */
    public function heatmap(Request $request): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonth());
            $endDate = $request->input('end_date', now());

            $heatmap = $this->auditService->getActivityHeatmap($startDate, $endDate);

            return $this->successResponse($heatmap);
        } catch (\Throwable $e) {
            Log::error('Failed to get activity heatmap', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve heatmap', 500);
        }
    }

    /**
     * Prune old audit logs.
     *
     * POST /admin/audit/prune
     */
    public function prune(Request $request): JsonResponse
    {
        try {
            $daysToKeep = $request->integer('days_to_keep', 90);
            $deleted = $this->auditService->pruneOldLogs($daysToKeep);

            return $this->successResponse([
                'deleted_count' => $deleted,
                'days_kept' => $daysToKeep,
            ], "Pruned {$deleted} old audit logs");
        } catch (\Throwable $e) {
            Log::error('Failed to prune audit logs', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to prune logs', 500);
        }
    }

    /**
     * Get user activity report.
     *
     * GET /admin/audit/user/{userId}/report
     */
    public function userActivityReport(Request $request, int $userId): JsonResponse
    {
        try {
            $startDate = $request->input('start_date', now()->subMonth());
            $endDate = $request->input('end_date', now());

            $report = $this->auditService->getUserActivityReport($userId, $startDate, $endDate);

            return $this->successResponse($report);
        } catch (\Throwable $e) {
            Log::error('Failed to get user activity report', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse('Failed to retrieve user activity report', 500);
        }
    }
}
