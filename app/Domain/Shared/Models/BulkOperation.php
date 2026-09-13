<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Enums\BulkOperationStatusEnum;
use App\Domain\Shared\Enums\BulkOperationTypeEnum;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * Bulk Operation Model
 *
 * @property int $id
 * @property string $operation_type
 * @property string $module
 * @property string $status
 * @property int $total_items
 * @property int $processed_count
 * @property int $success_count
 * @property int $failed_count
 * @property int $created_by
 * @property array|null $parameters
 * @property array|null $item_ids
 * @property array|null $result_data
 * @property array|null $error_log
 * @property bool $dry_run
 * @property bool $notify_on_completion
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $creator
 */
class BulkOperation extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'bulk_operations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'operation_type',
        'module',
        'status',
        'total_items',
        'processed_count',
        'success_count',
        'failed_count',
        'created_by',
        'parameters',
        'item_ids',
        'result_data',
        'error_log',
        'dry_run',
        'notify_on_completion',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'parameters' => 'array',
        'item_ids' => 'array',
        'result_data' => 'array',
        'error_log' => 'array',
        'dry_run' => 'boolean',
        'notify_on_completion' => 'boolean',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'operation_type' => BulkOperationTypeEnum::class,
        'status' => BulkOperationStatusEnum::class,
    ];

    /**
     * Get the user who created this operation
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Mark operation as started
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => BulkOperationStatusEnum::PROCESSING->value,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark operation as completed
     */
    public function markAsCompleted(): bool
    {
        $status = $this->failed_count > 0
            ? BulkOperationStatusEnum::PARTIALLY_COMPLETED->value
            : BulkOperationStatusEnum::COMPLETED->value;

        return $this->update([
            'status' => $status,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark operation as failed
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'timestamp' => now()->toDateTimeString(),
            'message' => $errorMessage,
        ];

        return $this->update([
            'status' => BulkOperationStatusEnum::FAILED->value,
            'error_log' => $errors,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark operation as cancelled
     */
    public function markAsCancelled(): bool
    {
        return $this->update([
            'status' => BulkOperationStatusEnum::CANCELLED->value,
            'completed_at' => now(),
        ]);
    }

    /**
     * Update progress
     */
    public function updateProgress(int $successCount, int $failedCount, ?array $errors = null): bool
    {
        $data = [
            'processed_count' => $this->processed_count + $successCount + $failedCount,
            'success_count' => $this->success_count + $successCount,
            'failed_count' => $this->failed_count + $failedCount,
        ];

        if ($errors !== null) {
            $currentErrors = $this->error_log ?? [];
            $data['error_log'] = array_merge($currentErrors, $errors);
        }

        return $this->update($data);
    }

    /**
     * Get progress percentage
     */
    public function getProgressPercentage(): float
    {
        if ($this->total_items === 0) {
            return 0.0;
        }

        return round(($this->processed_count / $this->total_items) * 100, 2);
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->processed_count === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->processed_count) * 100, 2);
    }

    /**
     * Get duration in seconds
     */
    public function getDuration(): ?int
    {
        if (! $this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();

        return (int) $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Check if operation can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            BulkOperationStatusEnum::PENDING,
            BulkOperationStatusEnum::PROCESSING,
        ], true);
    }

    /**
     * Check if operation is complete
     */
    public function isComplete(): bool
    {
        return $this->status->isFinal();
    }

    /**
     * Add error to log
     */
    public function addError(int $itemId, string $error): void
    {
        $errors = $this->error_log ?? [];
        $errors[] = [
            'item_id' => $itemId,
            'error' => $error,
            'timestamp' => now()->toDateTimeString(),
        ];

        $this->update(['error_log' => $errors]);
    }

    /**
     * Get readable operation type
     */
    public function getOperationTypeLabel(): string
    {
        if ($this->operation_type instanceof BulkOperationTypeEnum) {
            return $this->operation_type->label();
        }

        return BulkOperationTypeEnum::from($this->operation_type)->label();
    }

    /**
     * Get readable status
     */
    public function getStatusLabel(): string
    {
        if ($this->status instanceof BulkOperationStatusEnum) {
            return $this->status->label();
        }

        return BulkOperationStatusEnum::from($this->status)->label();
    }
}
