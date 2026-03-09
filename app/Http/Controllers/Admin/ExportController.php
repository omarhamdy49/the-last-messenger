<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Enums\ExportFormat;
use App\Domain\Shared\Enums\ExportModule;
use App\Domain\Shared\Exceptions\BusinessRuleViolationException;
use App\Domain\Shared\Models\Export;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Domain\Shared\Services\ExportGateway;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ExportRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export Controller
 * Handles bulk export of all modules to CSV/Excel.
 *
 * Module resolution and exporter construction are fully delegated to
 * ExportGateway, keeping this controller thin and free from domain
 * repository imports.
 */
class ExportController extends Controller
{
    public function __construct(
        protected AdminLoggerService $logger,
        protected ExportGateway $exportGateway,
    ) {}

    /**
     * Export data from the specified module.
     *
     * POST /su/portal/v1/admin/export
     */
    public function export(ExportRequest $request): BinaryFileResponse|JsonResponse
    {
        $data = $request->validatedWithDefaults();

        $exportRecord = Export::create([
            'user_id'   => $request->user()->id,
            'module'    => $data['module'],
            'format'    => $data['format'],
            'filters'   => $data['filters'],
            'status'    => 'pending',
            'file_name' => $this->generateFilename($data['module'], $data['format']),
        ]);

        try {
            $exporter = $this->exportGateway->buildExporter(
                moduleKey: $data['module'],
                format: $data['format'],
                language: $data['language'],
                filters: $data['filters'],
                columns: $data['columns'],
            );

            $recordCount = $exporter->collection()->count();
            $exportRecord->update(['record_count' => $recordCount]);

            $filename = $this->generateFilename($data['module'], $data['format']);
            $format   = ExportFormat::from($data['format']);
            $filePath = $exporter->exportToFile($format, pathinfo($filename, PATHINFO_FILENAME));

            $fileSize = file_exists($filePath) ? filesize($filePath) : 0;
            $exportRecord->markAsCompleted($filePath, $fileSize);

            $this->logger->logBulkAction(
                'export',
                Export::class,
                [$exportRecord->id],
                $request->user()
            );

            return response()->download($filePath, $filename, [
                'Content-Type'        => $format->getMimeType(),
                'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            ])->deleteFileAfterSend(true);
        } catch (BusinessRuleViolationException $e) {
            $exportRecord->markAsFailed();

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        } catch (\Throwable $e) {
            $exportRecord->markAsFailed();

            \Log::error('admin.export.failed', [
                'module'    => $data['module'],
                'format'    => $data['format'],
                'user_id'   => $request->user()->id,
                'error'     => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Export failed: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get available columns for a module (AJAX).
     *
     * GET /su/portal/v1/admin/export/{module}/columns
     */
    public function getColumns(string $module): JsonResponse
    {
        try {
            ExportModule::from($module); // validate the module key exists in the enum

            $columns = $this->exportGateway->getColumnsForModule($module);

            return response()->json([
                'success' => true,
                'data'    => $columns,
            ]);
        } catch (\ValueError) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid module',
            ], 400);
        } catch (BusinessRuleViolationException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get export history for the authenticated user.
     *
     * GET /su/portal/v1/admin/export/history
     */
    public function history(Request $request): JsonResponse
    {
        $perPage = $request->integer('per_page', 20);

        $exports = Export::with('user:id,name')
            ->select(['id', 'user_id', 'module', 'format', 'status', 'record_count', 'file_size', 'file_name', 'created_at'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data'    => $exports->items(),
            'meta'    => [
                'current_page' => $exports->currentPage(),
                'per_page'     => $exports->perPage(),
                'total'        => $exports->total(),
                'last_page'    => $exports->lastPage(),
            ],
        ]);
    }

    /**
     * Get export statistics for the authenticated user.
     *
     * GET /su/portal/v1/admin/export/stats
     */
    public function stats(Request $request): JsonResponse
    {
        $userId = $request->user()->id;

        $stats = [
            'total_exports'          => Export::where('user_id', $userId)->count(),
            'completed_exports'      => Export::where('user_id', $userId)->where('status', 'completed')->count(),
            'failed_exports'         => Export::where('user_id', $userId)->where('status', 'failed')->count(),
            'total_records_exported' => Export::where('user_id', $userId)->where('status', 'completed')->sum('record_count'),
            'total_file_size'        => Export::where('user_id', $userId)->where('status', 'completed')->sum('file_size'),
            'exports_by_module'      => Export::where('user_id', $userId)
                ->selectRaw('module, COUNT(*) as count')
                ->groupBy('module')
                ->get()
                ->pluck('count', 'module'),
            'exports_by_format'      => Export::where('user_id', $userId)
                ->selectRaw('format, COUNT(*) as count')
                ->groupBy('format')
                ->get()
                ->pluck('count', 'format'),
        ];

        return response()->json([
            'success' => true,
            'data'    => $stats,
        ]);
    }

    /**
     * Delete completed export files older than 24 hours.
     *
     * DELETE /su/portal/v1/admin/export/cleanup
     */
    public function cleanup(Request $request): JsonResponse
    {
        try {
            $oldExports   = Export::where('status', 'completed')
                ->where('created_at', '<', now()->subDay())
                ->get(['id', 'file_path']);

            $deletedCount = 0;
            foreach ($oldExports as $export) {
                if ($export->file_path && file_exists($export->file_path)) {
                    unlink($export->file_path);
                    $deletedCount++;
                }
            }

            Export::where('status', 'completed')
                ->where('created_at', '<', now()->subDay())
                ->update(['file_path' => null]);

            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deletedCount} ملف تصدير قديم",
                'data'    => ['deleted_count' => $deletedCount],
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'فشل تنظيف الملفات: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a timestamped filename for the export file.
     */
    private function generateFilename(string $module, string $format): string
    {
        $timestamp = now()->format('Ymd_His');

        return "{$module}-{$format}-{$timestamp}.{$format}";
    }
}
