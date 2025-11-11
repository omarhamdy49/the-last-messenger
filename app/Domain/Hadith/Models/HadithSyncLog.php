<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Hadith Sync Log Model
 * Tracks syncing operations with Sunnah.com API
 *
 * @property int $id
 * @property string|null $collection_name
 * @property string $status
 * @property string $sync_type
 * @property int $total_records
 * @property int $synced_records
 * @property int $failed_records
 * @property int $skipped_records
 * @property string|null $error_message
 * @property array|null $error_details
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 * @property int|null $duration_seconds
 * @property string|null $triggered_by
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class HadithSyncLog extends Model
{
    use HasFactory;

    protected $table = 'hadith_sync_logs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'collection_name',
        'status',
        'sync_type',
        'total_records',
        'synced_records',
        'failed_records',
        'skipped_records',
        'error_message',
        'error_details',
        'checkpoint',
        'metadata',
        'started_at',
        'completed_at',
        'duration_seconds',
        'triggered_by',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'total_records' => 'integer',
            'synced_records' => 'integer',
            'failed_records' => 'integer',
            'skipped_records' => 'integer',
            'error_details' => 'array',
            'checkpoint' => 'array',
            'metadata' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Mark sync as in progress
     */
    public function markInProgress(string $triggeredBy = 'command'): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
            'triggered_by' => $triggeredBy,
        ]);
    }

    /**
     * Mark sync as completed successfully
     */
    public function markSuccess(): void
    {
        $duration = $this->started_at ? abs(now()->diffInSeconds($this->started_at)) : 0;

        $this->update([
            'status' => 'success',
            'completed_at' => now(),
            'duration_seconds' => max(0, $duration),
        ]);
    }

    /**
     * Mark sync as failed
     */
    public function markFailed(string $errorMessage, array $errorDetails = []): void
    {
        $duration = $this->started_at ? abs(now()->diffInSeconds($this->started_at)) : 0;

        $this->update([
            'status' => 'failed',
            'error_message' => $errorMessage,
            'error_details' => $errorDetails,
            'completed_at' => now(),
            'duration_seconds' => max(0, $duration),
        ]);
    }

    /**
     * Mark sync as partial success
     */
    public function markPartial(string $message = ''): void
    {
        $duration = $this->started_at ? abs(now()->diffInSeconds($this->started_at)) : 0;

        $this->update([
            'status' => 'partial',
            'error_message' => $message,
            'completed_at' => now(),
            'duration_seconds' => max(0, $duration),
        ]);
    }

    /**
     * Increment synced records count
     */
    public function incrementSynced(int $count = 1): void
    {
        $this->increment('synced_records', $count);
    }

    /**
     * Increment failed records count
     */
    public function incrementFailed(int $count = 1): void
    {
        $this->increment('failed_records', $count);
    }

    /**
     * Increment skipped records count
     */
    public function incrementSkipped(int $count = 1): void
    {
        $this->increment('skipped_records', $count);
    }

    /**
     * Get success percentage
     */
    public function getSuccessPercentage(): float
    {
        if ($this->total_records === 0) {
            return 0;
        }

        return ($this->synced_records / $this->total_records) * 100;
    }

    /**
     * Update checkpoint data for resume capability
     */
    public function updateCheckpoint(array $checkpointData): void
    {
        $this->update([
            'checkpoint' => array_merge($this->checkpoint ?? [], $checkpointData),
        ]);
    }

    /**
     * Clear checkpoint data
     */
    public function clearCheckpoint(): void
    {
        $this->update(['checkpoint' => null]);
    }

    /**
     * Check if sync has checkpoint data
     */
    public function hasCheckpoint(): bool
    {
        return ! empty($this->checkpoint);
    }

    /**
     * Get checkpoint value by key
     */
    public function getCheckpoint(string $key, mixed $default = null): mixed
    {
        return $this->checkpoint[$key] ?? $default;
    }
}
