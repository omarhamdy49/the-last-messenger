<?php

declare(strict_types=1);

namespace App\Domain\Media\Models;

use App\Domain\Media\Enums\YouTubeSyncStatus;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Progress ledger for one queued playlist import. The job mutates it as it
 * works; the admin modal polls it for a live progress bar.
 *
 * @property int $id
 * @property int $program_id
 * @property string $playlist_url
 * @property array<int, array{video_id:string, episode_number:int, title:string}>|null $items
 * @property YouTubeSyncStatus $status
 * @property int $total
 * @property int $processed
 * @property int $created_count
 * @property int $updated_count
 * @property string|null $message
 * @property string|null $error
 * @property int|null $created_by
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class YouTubeSyncRun extends Model
{
    use HasFactory;

    protected $table = 'youtube_sync_runs';

    protected $fillable = [
        'program_id',
        'playlist_url',
        'items',
        'status',
        'total',
        'processed',
        'created_count',
        'updated_count',
        'message',
        'error',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'program_id' => 'integer',
        'items' => 'array',
        'status' => YouTubeSyncStatus::class,
        'total' => 'integer',
        'processed' => 'integer',
        'created_count' => 'integer',
        'updated_count' => 'integer',
        'created_by' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function program(): BelongsTo
    {
        return $this->belongsTo(MediaProgram::class, 'program_id');
    }

    public function progressPercent(): int
    {
        return $this->total > 0 ? (int) floor(min($this->processed, $this->total) / $this->total * 100) : 0;
    }

    // ------------------------------------------------------------------
    // State transitions — kept here so the service reads as a workflow.
    // ------------------------------------------------------------------

    public function markRunning(int $total, ?string $message = null): void
    {
        // Reset counters: a retried job re-walks the playlist from the start,
        // so stale progress from the interrupted attempt must not accumulate.
        $this->forceFill([
            'status' => YouTubeSyncStatus::Running,
            'total' => $total,
            'processed' => 0,
            'created_count' => 0,
            'updated_count' => 0,
            'error' => null,
            'message' => $message,
            'started_at' => $this->started_at ?? now(),
        ])->save();
    }

    public function recordItem(bool $created, string $message): void
    {
        $this->increment('processed');
        $this->increment($created ? 'created_count' : 'updated_count');
        $this->forceFill(['message' => $message])->save();
    }

    public function recordSkip(string $message): void
    {
        $this->increment('processed');
        $this->forceFill(['message' => $message])->save();
    }

    public function markCompleted(?string $message = null): void
    {
        $this->forceFill([
            'status' => YouTubeSyncStatus::Completed,
            'message' => $message,
            'finished_at' => now(),
        ])->save();
    }

    public function markFailed(string $error): void
    {
        $this->forceFill([
            'status' => YouTubeSyncStatus::Failed,
            'error' => mb_substr($error, 0, 60000),
            'finished_at' => now(),
        ])->save();
    }
}
