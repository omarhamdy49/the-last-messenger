<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Blog\Models\Article;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Quran\Models\Verse;
use App\Domain\Sahaba\Models\Sahabi;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Enums\BulkOperationStatusEnum;
use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use App\Domain\Shared\Models\BulkOperation;
use App\Domain\Shared\Repositories\BulkOperationRepositoryInterface;
use App\Jobs\ProcessBulkOperation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Bulk Operation Service
 * Handles all bulk operations for content management
 */
class BulkOperationService extends BaseService
{
    private const QUEUE_THRESHOLD = 500; // Queue if more than 500 items
    private const CHUNK_SIZE = 100; // Process 100 items per chunk

    /**
     * Constructor
     */
    public function __construct(
        BulkOperationRepositoryInterface $repository,
        private readonly AdminLoggerService $adminLogger
    ) {
        $this->repository = $repository;
    }

    /**
     * Create and start bulk operation
     *
     * @param  array<int>  $itemIds
     * @param  array<string, mixed>  $parameters
     */
    public function createOperation(
        string $operationType,
        string $module,
        array $itemIds,
        int $userId,
        array $parameters = [],
        bool $dryRun = false
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

        // Log operation creation
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

        // Dispatch to queue if large operation
        if (count($itemIds) > self::QUEUE_THRESHOLD) {
            ProcessBulkOperation::dispatch($operation);
        } else {
            // Process immediately for smaller operations
            $this->processOperation($operation);
        }

        return $operation;
    }

    /**
     * Process bulk operation
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

            match ($operationType) {
                BulkOperationTypeEnum::UPDATE => $this->processBulkUpdate($operation),
                BulkOperationTypeEnum::DELETE => $this->processBulkDelete($operation, false),
                BulkOperationTypeEnum::SOFT_DELETE => $this->processBulkDelete($operation, true),
                BulkOperationTypeEnum::RESTORE => $this->processBulkRestore($operation),
                BulkOperationTypeEnum::PUBLISH => $this->processBulkPublish($operation),
                BulkOperationTypeEnum::UNPUBLISH => $this->processBulkUnpublish($operation),
                BulkOperationTypeEnum::ARCHIVE => $this->processBulkArchive($operation),
                BulkOperationTypeEnum::CHANGE_CATEGORY => $this->processBulkChangeCategory($operation),
                BulkOperationTypeEnum::ADD_TAGS => $this->processBulkAddTags($operation),
                BulkOperationTypeEnum::REMOVE_TAGS => $this->processBulkRemoveTags($operation),
                BulkOperationTypeEnum::CHANGE_STATUS => $this->processBulkChangeStatus($operation),
                BulkOperationTypeEnum::CHANGE_AUTHOR => $this->processBulkChangeAuthor($operation),
                BulkOperationTypeEnum::ASSIGN_USER => $this->processBulkAssignUser($operation),
                BulkOperationTypeEnum::EXPORT => $this->processBulkExport($operation),
                default => throw new \InvalidArgumentException("Unsupported operation type: {$operation->operation_type}"),
            };

            $operation->markAsCompleted();

            // Clear module cache
            $this->clearModuleCache($operation->module);

            // Log completion
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

            Log::error('Bulk operation failed', [
                'operation_id' => $operation->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Process bulk update
     */
    private function processBulkUpdate(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $fields = $operation->parameters['fields'] ?? [];

        if (empty($fields)) {
            throw new \InvalidArgumentException('No fields provided for update');
        }

        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $model::where('id', $itemId)->update($fields);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk delete
     */
    private function processBulkDelete(BulkOperation $operation, bool $softDelete): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item) {
                        if ($softDelete) {
                            $item->delete(); // Soft delete
                        } else {
                            $item->forceDelete(); // Permanent delete
                        }
                        $successCount++;
                    } else {
                        $failedCount++;
                        $operation->addError($itemId, 'Item not found');
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk restore
     */
    private function processBulkRestore(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::withTrashed()->find($itemId);

                    if ($item && $item->trashed()) {
                        $item->restore();
                        $successCount++;
                    } else {
                        $failedCount++;
                        $operation->addError($itemId, 'Item not found or not trashed');
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk publish
     */
    private function processBulkPublish(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item && method_exists($item, 'publish')) {
                        $item->publish();
                        $successCount++;
                    } else {
                        // Fallback to status update
                        $model::where('id', $itemId)->update([
                            'status' => 'published',
                            'published_at' => now(),
                        ]);
                        $successCount++;
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk unpublish
     */
    private function processBulkUnpublish(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item && method_exists($item, 'unpublish')) {
                        $item->unpublish();
                        $successCount++;
                    } else {
                        // Fallback to status update
                        $model::where('id', $itemId)->update([
                            'status' => 'draft',
                        ]);
                        $successCount++;
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk archive
     */
    private function processBulkArchive(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item && method_exists($item, 'archive')) {
                        $item->archive();
                        $successCount++;
                    } else {
                        // Fallback to status update
                        $model::where('id', $itemId)->update([
                            'status' => 'archived',
                        ]);
                        $successCount++;
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk change category
     */
    private function processBulkChangeCategory(BulkOperation $operation): void
    {
        $categoryId = $operation->parameters['category_id'] ?? null;

        if (! $categoryId) {
            throw new \InvalidArgumentException('Category ID is required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $model::where('id', $itemId)->update(['category_id' => $categoryId]);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk add tags
     */
    private function processBulkAddTags(BulkOperation $operation): void
    {
        $tagIds = $operation->parameters['tag_ids'] ?? [];

        if (empty($tagIds)) {
            throw new \InvalidArgumentException('Tag IDs are required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item && method_exists($item, 'tags')) {
                        $item->tags()->syncWithoutDetaching($tagIds);
                        $successCount++;
                    } else {
                        $failedCount++;
                        $operation->addError($itemId, 'Item does not support tags');
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk remove tags
     */
    private function processBulkRemoveTags(BulkOperation $operation): void
    {
        $tagIds = $operation->parameters['tag_ids'] ?? [];

        if (empty($tagIds)) {
            throw new \InvalidArgumentException('Tag IDs are required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $item = $model::find($itemId);

                    if ($item && method_exists($item, 'tags')) {
                        $item->tags()->detach($tagIds);
                        $successCount++;
                    } else {
                        $failedCount++;
                        $operation->addError($itemId, 'Item does not support tags');
                    }
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk change status
     */
    private function processBulkChangeStatus(BulkOperation $operation): void
    {
        $status = $operation->parameters['status'] ?? null;

        if (! $status) {
            throw new \InvalidArgumentException('Status is required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $model::where('id', $itemId)->update(['status' => $status]);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk change author
     */
    private function processBulkChangeAuthor(BulkOperation $operation): void
    {
        $authorId = $operation->parameters['author_id'] ?? null;

        if (! $authorId) {
            throw new \InvalidArgumentException('Author ID is required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $model::where('id', $itemId)->update(['author_id' => $authorId]);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk assign user
     */
    private function processBulkAssignUser(BulkOperation $operation): void
    {
        $userId = $operation->parameters['user_id'] ?? null;

        if (! $userId) {
            throw new \InvalidArgumentException('User ID is required');
        }

        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;
        $chunks = array_chunk($itemIds, self::CHUNK_SIZE);

        foreach ($chunks as $chunk) {
            $successCount = 0;
            $failedCount = 0;

            foreach ($chunk as $itemId) {
                try {
                    $model::where('id', $itemId)->update(['assigned_to' => $userId]);
                    $successCount++;
                } catch (\Throwable $e) {
                    $failedCount++;
                    $operation->addError($itemId, $e->getMessage());
                }
            }

            $operation->updateProgress($successCount, $failedCount);
        }
    }

    /**
     * Process bulk export
     */
    private function processBulkExport(BulkOperation $operation): void
    {
        $format = $operation->parameters['format'] ?? 'json';
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;

        try {
            $items = $model::whereIn('id', $itemIds)->get();

            $exportData = match ($format) {
                'json' => $items->toJson(),
                'csv' => $this->convertToCsv($items),
                default => throw new \InvalidArgumentException("Unsupported export format: {$format}"),
            };

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
     * Process dry run (preview mode)
     */
    private function processDryRun(BulkOperation $operation): void
    {
        $model = $this->getModelClass($operation->module);
        $itemIds = $operation->item_ids;

        // Get sample of items that would be affected
        $sampleItems = $model::whereIn('id', array_slice($itemIds, 0, 10))->get();

        $operation->update([
            'status' => BulkOperationStatusEnum::COMPLETED->value,
            'result_data' => [
                'dry_run' => true,
                'would_affect' => count($itemIds),
                'sample_items' => $sampleItems->map(fn ($item) => [
                    'id' => $item->id,
                    'title' => $item->title ?? $item->name ?? 'N/A',
                ]),
            ],
            'completed_at' => now(),
        ]);
    }

    /**
     * Cancel bulk operation
     */
    public function cancelOperation(int $operationId): bool
    {
        $operation = $this->repository->findOrFail($operationId);

        if (! $operation->canBeCancelled()) {
            throw new \RuntimeException('Operation cannot be cancelled');
        }

        return $operation->markAsCancelled();
    }

    /**
     * Get operation status
     */
    public function getOperationStatus(int $operationId): BulkOperation
    {
        return $this->repository->findOrFail($operationId, ['*'], ['creator']);
    }

    /**
     * Get operation history
     */
    public function getOperationHistory(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByUser($userId, $perPage);
    }

    /**
     * Get model class for module
     */
    private function getModelClass(string $module): string
    {
        return match ($module) {
            'articles' => Article::class,
            'hadith' => Hadith::class,
            'media_programs' => MediaProgram::class,
            'seerah_events' => SeerahEvent::class,
            'sahaba' => Sahabi::class,
            'quran_verses' => Verse::class,
            default => throw new \InvalidArgumentException("Unsupported module: {$module}"),
        };
    }

    /**
     * Clear module cache
     */
    private function clearModuleCache(string $module): void
    {
        $cacheKeys = [
            'articles' => ['articles'],
            'hadith' => ['hadith'],
            'media_programs' => ['media_programs'],
            'seerah_events' => ['seerah_events'],
            'sahaba' => ['sahaba'],
            'quran_verses' => ['verses'],
        ];

        if (isset($cacheKeys[$module])) {
            foreach ($cacheKeys[$module] as $key) {
                Cache::tags([$key])->flush();
            }
        }
    }

    /**
     * Convert collection to CSV
     */
    private function convertToCsv(Collection $items): string
    {
        if ($items->isEmpty()) {
            return '';
        }

        $csv = '';
        $headers = array_keys($items->first()->toArray());
        $csv .= implode(',', $headers)."\n";

        foreach ($items as $item) {
            $row = array_map(fn ($value) => is_array($value) ? json_encode($value) : $value, $item->toArray());
            $csv .= implode(',', $row)."\n";
        }

        return $csv;
    }
}
