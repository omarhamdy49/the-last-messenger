<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Contracts\BulkOperationHandlerInterface;
use App\Domain\Shared\Enums\BulkOperationStatusEnum;
use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use App\Domain\Shared\Models\BulkOperation;
use App\Domain\Shared\Repositories\BulkOperationRepositoryInterface;
use App\Jobs\ProcessBulkOperation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Operation Service
 *
 * Orchestrates bulk operations across all content modules.
 * Domain knowledge lives exclusively in registered BulkOperationHandlerInterface
 * implementations — this service has no direct dependency on domain models.
 *
 * @extends BaseService<BulkOperationRepositoryInterface>
 */
class BulkOperationService extends BaseService
{
    private const QUEUE_THRESHOLD = 500;

    private const CHUNK_SIZE = 100;

    /** @var array<string, BulkOperationHandlerInterface> Keyed by module string */
    private array $handlers = [];

    /**
     * @param  BulkOperationHandlerInterface[]  $handlers
     */
    public function __construct(
        BulkOperationRepositoryInterface $repository,
        private readonly AdminLoggerService $adminLogger,
        array $handlers = [],
    ) {
        $this->repository = $repository;

        foreach ($handlers as $handler) {
            $this->handlers[$handler->getSupportedModule()] = $handler;
        }
    }

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Create and immediately start (or queue) a bulk operation.
     *
     * @param  int[]  $itemIds
     * @param  array<string, mixed>  $parameters
     */
    public function createOperation(
        string $operationType,
        string $module,
        array $itemIds,
        int $userId,
        array $parameters = [],
        bool $dryRun = false,
    ): BulkOperation {
        $operation = $this->repository->create([
            'operation_type' => $operationType,
            'module' => $module,
            'status' => BulkOperationStatusEnum::PENDING->value,
            'total_items' => count($itemIds),
            'item_ids' => $itemIds,
            'parameters' => $parameters,
            'created_by' => $userId,
            'dry_run' => $dryRun,
        ]);

        $this->adminLogger->logCustomAction(
            action: 'bulk_operation_created',
            model: $operation,
            user: $operation->creator,
            newValues: [
                'operation_type' => $operationType,
                'module' => $module,
                'total_items' => count($itemIds),
            ]
        );

        if (count($itemIds) > self::QUEUE_THRESHOLD) {
            ProcessBulkOperation::dispatch($operation);
        } else {
            $this->processOperation($operation);
        }

        return $operation;
    }

    /**
     * Process a bulk operation record — called directly or from the queue job.
     */
    public function processOperation(BulkOperation $operation): void
    {
        if ($operation->dry_run) {
            $this->processDryRun($operation);

            return;
        }

        $operation->markAsStarted();

        try {
            $operationType = BulkOperationTypeEnum::from($operation->operation_type);

            if ($operationType === BulkOperationTypeEnum::EXPORT) {
                $this->processExport($operation);
            } else {
                $this->dispatchToHandler($operation);
            }

            $operation->markAsCompleted();

            $this->clearModuleCache($operation->module);

            $this->adminLogger->logCustomAction(
                action: 'bulk_operation_completed',
                model: $operation,
                user: $operation->creator,
                newValues: [
                    'success_count' => $operation->success_count,
                    'failed_count' => $operation->failed_count,
                    'success_rate' => $operation->getSuccessRate(),
                ]
            );
        } catch (\Throwable $e) {
            $operation->markAsFailed($e->getMessage());

            Log::error('bulk_operation.failed', [
                'operation_id' => $operation->id,
                'module' => $operation->module,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Cancel a pending or running operation.
     */
    public function cancelOperation(int $operationId): bool
    {
        $operation = $this->repository->findOrFail($operationId);

        if (! $operation->canBeCancelled()) {
            throw new \RuntimeException('Operation cannot be cancelled in its current state.');
        }

        return $operation->markAsCancelled();
    }

    /**
     * Return current status for an operation.
     */
    /**
     * Fetch one operation, optionally scoped to its owner.
     *
     * $forUserId null means "no ownership restriction" and is reserved for super admins —
     * the same predicate index() and getOperationStatistics() already use. Without it the
     * {id} routes were a straight IDOR: an editor could walk
     * /bulk-operations/1..N/download and read another admin's export payload, which
     * includes the submitted request_data.
     *
     * @throws ModelNotFoundException when the operation does not exist OR belongs to
     *                                someone else — deliberately indistinguishable, so the
     *                                endpoint cannot be used to enumerate ids.
     */
    public function getOperationStatus(int $operationId, ?int $forUserId = null): BulkOperation
    {
        /** @var BulkOperation $operation */
        $operation = $this->repository->findOrFail($operationId, ['*'], ['creator']);

        if ($forUserId !== null && (int) $operation->created_by !== $forUserId) {
            throw new ModelNotFoundException("Bulk operation [{$operationId}] not found.");
        }

        return $operation;
    }

    /**
     * Return paginated operation history for a user.
     */
    public function getOperationHistory(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByUser($userId, $perPage);
    }

    /**
     * Return operation counts per status, plus the total.
     *
     * Every status the enum defines is present in the result (zero-filled) so
     * callers get a stable shape; $userId scopes to one creator, null is
     * platform-wide.
     *
     * @return array<string, int>
     */
    public function getOperationStatistics(?int $userId = null): array
    {
        $counts = $this->repository->countByStatus($userId);

        $stats = [];
        foreach (BulkOperationStatusEnum::values() as $status) {
            $stats[$status] = $counts[$status] ?? 0;
        }

        // Sum the raw counts, not $stats: a status row that is no longer in the
        // enum must still be counted in the total rather than silently dropped.
        $stats['total_operations'] = array_sum($counts);

        return $stats;
    }

    // -------------------------------------------------------------------------
    // Private orchestration
    // -------------------------------------------------------------------------

    /**
     * Route non-export operations to the registered domain handler in chunks.
     */
    private function dispatchToHandler(BulkOperation $operation): void
    {
        $handler = $this->resolveHandler($operation->module);
        $chunks = array_chunk($operation->item_ids, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $result = $handler->handle(
                $operation->operation_type,
                $chunk,
                $operation->parameters ?? [],
            );

            $operation->updateProgress($result['successCount'], $result['failedCount']);

            foreach ($result['errors'] as $itemId => $message) {
                $operation->addError($itemId, $message);
            }
        }
    }

    /**
     * Handle export operations — delegates serialization to the handler.
     */
    private function processExport(BulkOperation $operation): void
    {
        $handler = $this->resolveHandler($operation->module);
        $format = $operation->parameters['format'] ?? 'json';
        $itemIds = $operation->item_ids;

        try {
            $exportData = $handler->export($itemIds, $format);

            $operation->update([
                'result_data' => [
                    'format' => $format,
                    'file_size' => strlen($exportData),
                    'data' => $exportData,
                ],
                'success_count' => count($itemIds),
                'processed_count' => count($itemIds),
            ]);
        } catch (\Throwable $e) {
            $operation->update([
                'failed_count' => count($itemIds),
                'processed_count' => count($itemIds),
            ]);
            $operation->addError(0, $e->getMessage());

            throw $e;
        }
    }

    /**
     * Preview mode: record what would be affected without performing any writes.
     */
    private function processDryRun(BulkOperation $operation): void
    {
        $handler = $this->resolveHandler($operation->module);
        $itemIds = $operation->item_ids;
        $sampleItems = $handler->preview($itemIds);

        $operation->update([
            'status' => BulkOperationStatusEnum::COMPLETED->value,
            'result_data' => [
                'dry_run' => true,
                'would_affect' => count($itemIds),
                'sample_items' => $sampleItems,
            ],
            'completed_at' => now(),
        ]);
    }

    /**
     * Resolve the handler for the given module or throw.
     *
     * @throws \InvalidArgumentException
     */
    private function resolveHandler(string $module): BulkOperationHandlerInterface
    {
        return $this->handlers[$module]
            ?? throw new \InvalidArgumentException(
                "No bulk operation handler registered for module [{$module}]."
            );
    }

    /**
     * Invalidate the cache for the given module after a successful bulk operation.
     *
     * Cache::tags() requires Redis/Memcached and is unavailable with the file
     * or database drivers. Each affected repository uses a version counter
     * (stored under "{prefix}:version") as a tag emulation. Bumping that
     * counter orphans every versioned key previously written by that repository,
     * which is equivalent to a tag flush and works with any cache driver.
     */
    private function clearModuleCache(string $module): void
    {
        // Map module names to their repository cache-version keys.
        // These match the cachePrefix values derived from each model's
        // class basename (lowercased) in BaseRepository::getCachePrefix().
        $versionKeyMap = [
            'articles' => 'article:version',
            'hadith' => 'hadith:version',
            'seerah_events' => 'seerahevent:version',
            'sahaba' => 'sahabi:version',
            'quran_verses' => 'verse:version',
        ];

        if (! isset($versionKeyMap[$module])) {
            return;
        }

        $versionKey = $versionKeyMap[$module];
        Cache::forever($versionKey, ((int) Cache::get($versionKey, 0)) + 1);
    }
}
