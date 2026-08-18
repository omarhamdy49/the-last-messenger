<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Services\BulkOperationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkOperationRequest;
use App\Http\Resources\BulkOperationResource;
use App\Support\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Operation Controller
 * Handles bulk operations for content management
 */
class BulkOperationController extends Controller
{
    use ApiResponse;

    /**
     * Constructor
     */
    public function __construct(
        private readonly BulkOperationService $bulkOperationService
    ) {
        $this->middleware('auth:admin');
    }

    /**
     * Start a new bulk operation
     *
     * POST /admin/bulk-operations
     */
    public function store(BulkOperationRequest $request): JsonResponse
    {
        try {
            $operation = $this->bulkOperationService->createOperation(
                operationType: $request->input('operation_type'),
                module: $request->input('module'),
                itemIds: $request->input('item_ids'),
                userId: $request->user()->id,
                parameters: $request->input('parameters', []),
                dryRun: $request->boolean('dry_run', false)
            );

            Log::info('Bulk operation created', [
                'operation_id' => $operation->id,
                'user_id' => $request->user()->id,
                'operation_type' => $operation->operation_type,
                'module' => $operation->module,
                'total_items' => $operation->total_items,
            ]);

            return $this->createdResponse(
                new BulkOperationResource($operation),
                'Bulk operation started successfully'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to create bulk operation', [
                'error' => $e->getMessage(),
                'user_id' => $request->user()->id,
                'request_data' => $request->all(),
            ]);

            return $this->errorResponse(
                'Failed to start bulk operation: '.$e->getMessage(),
                500
            );
        }
    }

    /**
     * Get bulk operation status
     *
     * GET /admin/bulk-operations/{id}/status
     */
    public function status(Request $request, string $id): JsonResponse
    {
        try {
            $operation = $this->bulkOperationService->getOperationStatus((int) $id, $this->ownerScope($request));

            return $this->successResponse(
                new BulkOperationResource($operation),
                'Operation status retrieved successfully'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get operation status', [
                'operation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->notFoundResponse('Operation not found');
        }
    }

    /**
     * Get operation history
     *
     * GET /admin/bulk-operations
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $perPage = (int) $request->input('per_page', 15);
            $operations = $this->bulkOperationService->getOperationHistory(
                userId: $request->user()->id,
                perPage: $perPage
            );

            return $this->paginatedResponse(
                $operations->setCollection(
                    $operations->getCollection()->map(fn ($operation) => new BulkOperationResource($operation))
                ),
                'Operation history retrieved successfully'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get operation history', [
                'user_id' => $request->user()->id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve operation history',
                500
            );
        }
    }

    /**
     * Cancel an operation
     *
     * DELETE /admin/bulk-operations/{id}
     */
    public function destroy(string $id, Request $request): JsonResponse
    {
        try {
            // Resolve under the caller's scope first: cancelOperation() takes only an id,
            // so without this an editor could cancel another admin's running operation.
            $this->bulkOperationService->getOperationStatus((int) $id, $this->ownerScope($request));

            $cancelled = $this->bulkOperationService->cancelOperation((int) $id);

            if (! $cancelled) {
                return $this->errorResponse(
                    'Operation cannot be cancelled',
                    400
                );
            }

            Log::info('Bulk operation cancelled', [
                'operation_id' => $id,
                'user_id' => $request->user()->id,
            ]);

            return $this->successResponse(
                null,
                'Operation cancelled successfully'
            );
        } catch (\RuntimeException $e) {
            return $this->errorResponse($e->getMessage(), 400);
        } catch (\Throwable $e) {
            Log::error('Failed to cancel operation', [
                'operation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to cancel operation',
                500
            );
        }
    }

    /**
     * Download operation results
     *
     * GET /admin/bulk-operations/{id}/download
     */
    public function download(Request $request, string $id): JsonResponse
    {
        try {
            $operation = $this->bulkOperationService->getOperationStatus((int) $id, $this->ownerScope($request));

            if (! $operation->isComplete()) {
                return $this->errorResponse(
                    'Operation is not complete yet',
                    400
                );
            }

            $resultData = $operation->result_data;

            if (! $resultData || ! isset($resultData['data'])) {
                return $this->errorResponse(
                    'No export data available',
                    404
                );
            }

            return $this->successResponse([
                'format' => $resultData['format'] ?? 'json',
                'file_size' => $resultData['file_size'] ?? 0,
                'data' => $resultData['data'],
                'filename' => "bulk_export_{$operation->id}_{$operation->module}.{$resultData['format']}",
            ], 'Export data retrieved successfully');
        } catch (\Throwable $e) {
            Log::error('Failed to download operation results', [
                'operation_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to download operation results',
                500
            );
        }
    }

    /**
     * Get operation statistics
     *
     * GET /admin/bulk-operations/stats
     */
    public function stats(Request $request): JsonResponse
    {
        try {
            // Scoped like index(): a super admin sees the whole platform,
            // everyone else only their own operations.
            $userId = $request->user()->hasRole('super_admin')
                ? null
                : $request->user()->id;

            return $this->successResponse(
                $this->bulkOperationService->getOperationStatistics($userId),
                'Statistics retrieved successfully'
            );
        } catch (\Throwable $e) {
            Log::error('Failed to get operation statistics', [
                'error' => $e->getMessage(),
            ]);

            return $this->errorResponse(
                'Failed to retrieve statistics',
                500
            );
        }
    }

    /**
     * null for a super admin (platform-wide), otherwise the caller's own id.
     *
     * index() and stats() already scoped this way; download(), status() and destroy() took
     * a raw {id} and did not, which made them an IDOR onto other admins' operations and
     * their result_data (which carries the submitted request_data).
     */
    private function ownerScope(Request $request): ?int
    {
        return $request->user()->hasRole('super_admin') ? null : (int) $request->user()->id;
    }
}
