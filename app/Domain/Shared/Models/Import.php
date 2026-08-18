<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Import Model
 *
 * Tracks file import operations
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property string $file_name
 * @property string $file_path
 * @property string $file_type
 * @property int $file_size
 * @property int $total_rows
 * @property int $success_count
 * @property int $error_count
 * @property int $skipped_count
 * @property string $status
 * @property array|null $result_data
 * @property array|null $options
 * @property Carbon|null $started_at
 * @property Carbon|null $completed_at
 * @property int|null $processing_time_seconds
 * @property string|null $error_message
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Import extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'module',
        'file_name',
        'file_path',
        'file_type',
        'file_size',
        'total_rows',
        'success_count',
        'error_count',
        'skipped_count',
        'status',
        'result_data',
        'options',
        'started_at',
        'completed_at',
        'processing_time_seconds',
        'error_message',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'result_data' => 'array',
            'options' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'file_size' => 'integer',
            'total_rows' => 'integer',
            'success_count' => 'integer',
            'error_count' => 'integer',
            'skipped_count' => 'integer',
            'processing_time_seconds' => 'integer',
        ];
    }

    /**
     * Get the user who initiated the import
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all import errors
     */
    public function errors(): HasMany
    {
        return $this->hasMany(ImportError::class);
    }

    /**
     * Get all import mappings
     */
    public function mappings(): HasMany
    {
        return $this->hasMany(ImportMapping::class);
    }

    /**
     * Check if import is pending
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if import is processing
     */
    public function isProcessing(): bool
    {
        return in_array($this->status, ['parsing', 'validating', 'processing']);
    }

    /**
     * Check if import is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if import has failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Mark import as started
     */
    public function markAsStarted(): bool
    {
        return $this->update([
            'status' => 'processing',
            'started_at' => now(),
        ]);
    }

    /**
     * Mark import as completed
     *
     * @param  array<string, mixed>  $resultData
     */
    public function markAsCompleted(array $resultData = []): bool
    {
        $completedAt = now();
        $processingTime = $this->started_at
            ? $completedAt->diffInSeconds($this->started_at)
            : null;

        return $this->update([
            'status' => 'completed',
            'completed_at' => $completedAt,
            'processing_time_seconds' => $processingTime,
            'result_data' => array_merge($this->result_data ?? [], $resultData),
        ]);
    }

    /**
     * Mark import as failed
     */
    public function markAsFailed(string $errorMessage): bool
    {
        $completedAt = now();
        $processingTime = $this->started_at
            ? $completedAt->diffInSeconds($this->started_at)
            : null;

        return $this->update([
            'status' => 'failed',
            'completed_at' => $completedAt,
            'processing_time_seconds' => $processingTime,
            'error_message' => $errorMessage,
        ]);
    }

    /**
     * Get file size in human-readable format
     */
    public function getFileSizeHuman(): string
    {
        $bytes = $this->file_size;
        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 2).' '.$units[$i];
    }

    /**
     * Get success rate percentage
     */
    public function getSuccessRate(): float
    {
        if ($this->total_rows === 0) {
            return 0.0;
        }

        return round(($this->success_count / $this->total_rows) * 100, 2);
    }
}
