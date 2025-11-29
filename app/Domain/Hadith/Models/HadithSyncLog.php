<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Class HadithSyncLog
 *
 * Tracks synchronization progress for hadith collections and books.
 * Used for resume functionality and monitoring sync operations.
 *
 * @property int $id
 * @property string $collection_name Collection being synced
 * @property string $type Type of sync (collection, book)
 * @property string $status Sync status (pending, in_progress, completed, failed)
 * @property int $synced_records Number of successfully synced records
 * @property int $failed_records Number of failed records
 * @property array|null $checkpoint Resume point for failed syncs
 * @property array|null $metadata Additional sync metadata
 * @property \Illuminate\Support\Carbon|null $started_at When sync started
 * @property \Illuminate\Support\Carbon|null $completed_at When sync completed
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class HadithSyncLog extends Model
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'hadith_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'collection_name',
        'type',
        'status',
        'metadata',
        'summary',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'metadata' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';

    /**
     * Type constants.
     */
    public const TYPE_COLLECTION = 'collection';
    public const TYPE_BOOK = 'book';

    /**
     * Mark sync as started.
     */
    public function markStarted(): void
    {
        $this->update([
            'status' => self::STATUS_IN_PROGRESS,
            'started_at' => now(),
        ]);
    }

    /**
     * Mark sync as completed.
     */
    public function markCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark sync as failed.
     */
    public function markFailed(string $errorMessage, array $additionalData = []): void
    {
        $metadata = array_merge($this->metadata ?? [], [
            'error' => $errorMessage,
            'failed_at' => now()->toDateTimeString(),
        ], $additionalData);

        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Update sync progress with summary data.
     */
    public function updateProgress(array $summary): void
    {
        $this->update([
            'summary' => $summary,
        ]);
    }

    /**
     * Check if checkpoint data exists.
     */
    public function hasCheckpoint(): bool
    {
        return !empty($this->checkpoint) && is_array($this->checkpoint);
    }

    /**
     * Get specific checkpoint value.
     */
    public function getCheckpoint(string $key, mixed $default = null): mixed
    {
        return $this->checkpoint[$key] ?? $default;
    }

    /**
     * Clear checkpoint data.
     */
    public function clearCheckpoint(): bool
    {
        return $this->update(['checkpoint' => []]);
    }

    /**
     * Increment synced records counter.
     * @deprecated Use updateProgress() with summary array instead
     */
    public function incrementSynced(int $count = 1): void
    {
        // No-op: Column removed from schema
        // Use updateProgress() with summary array instead
    }

    /**
     * Increment failed records counter.
     * @deprecated Use updateProgress() with summary array instead
     */
    public function incrementFailed(int $count = 1): void
    {
        // No-op: Column removed from schema
        // Use updateProgress() with summary array instead
    }

    /**
     * Check if sync can be resumed.
     */
    public function canBeResumed(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_FAILED])
            && $this->checkpoint !== null;
    }

    /**
     * Check if sync is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if sync is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if sync has failed.
     */
    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    /**
     * Get sync duration in seconds.
     */
    public function getDurationInSeconds(): ?int
    {
        if (!$this->started_at) {
            return null;
        }

        $endTime = $this->completed_at ?? now();
        return $this->started_at->diffInSeconds($endTime);
    }

    /**
     * Get human-readable duration.
     */
    public function getHumanDuration(): ?string
    {
        $seconds = $this->getDurationInSeconds();

        if ($seconds === null) {
            return null;
        }

        if ($seconds < 60) {
            return "{$seconds} seconds";
        }

        $minutes = floor($seconds / 60);
        $remainingSeconds = $seconds % 60;

        if ($minutes < 60) {
            return "{$minutes} minutes, {$remainingSeconds} seconds";
        }

        $hours = floor($minutes / 60);
        $remainingMinutes = $minutes % 60;

        return "{$hours} hours, {$remainingMinutes} minutes";
    }

    /**
     * Scope to filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by collection.
     */
    public function scopeByCollection($query, string $collectionName)
    {
        return $query->where('collection_name', $collectionName);
    }

    /**
     * Scope to get in-progress syncs.
     */
    public function scopeInProgress($query)
    {
        return $query->where('status', self::STATUS_IN_PROGRESS);
    }

    /**
     * Scope to get failed syncs.
     */
    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    /**
     * Scope to get completed syncs.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope to get resumable syncs.
     */
    public function scopeResumable($query)
    {
        return $query->whereIn('status', [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_FAILED])
            ->whereNotNull('checkpoint');
    }
}
