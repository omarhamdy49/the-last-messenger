<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HadithSyncLog extends Model
{
    protected $table = 'hadith_sync_logs';

    protected $fillable = [
        'collection_id',
        'source_collection_code',
        'type',
        'status',
        'metadata',
        'summary',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'collection_id' => 'integer',
        'metadata' => 'array',
        'summary' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const STATUS_PENDING = 'pending';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const TYPE_JSON_IMPORT = 'json_import';

    public function collection(): BelongsTo
    {
        return $this->belongsTo(HadithCollection::class, 'collection_id');
    }

    public function markStarted(): void
    {
        $this->update(['status' => self::STATUS_IN_PROGRESS, 'started_at' => now()]);
    }

    public function markCompleted(): void
    {
        $this->update(['status' => self::STATUS_COMPLETED, 'completed_at' => now()]);
    }

    public function markFailed(string $errorMessage, array $additionalData = []): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'completed_at' => now(),
            'error_message' => $errorMessage,
            'metadata' => array_merge($this->metadata ?? [], $additionalData),
        ]);
    }

    public function updateProgress(array $summary): void
    {
        $this->update(['summary' => $summary]);
    }

    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function hasFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeByCollection($query, string $sourceCollectionCode)
    {
        return $query->where('source_collection_code', $sourceCollectionCode);
    }
}
