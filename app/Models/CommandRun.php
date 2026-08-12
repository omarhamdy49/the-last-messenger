<?php

declare(strict_types=1);

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One execution of a whitelisted Artisan command started from the admin panel.
 *
 * The row is the only channel between the worker and the browser: the job
 * streams console output and progress into it, the settings modal polls it.
 *
 * @property int $id
 * @property string $command
 * @property array<string, mixed>|null $input
 * @property string $status
 * @property bool $is_queued
 * @property int $progress_current
 * @property int $progress_total
 * @property string|null $message
 * @property string|null $output
 * @property int|null $exit_code
 * @property string|null $error
 * @property int|null $created_by
 * @property Carbon|null $started_at
 * @property Carbon|null $finished_at
 */
class CommandRun extends Model
{
    public const STATUS_QUEUED = 'queued';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    /** Console output kept per run; the tail is what matters when it overflows. */
    public const OUTPUT_LIMIT = 200_000;

    protected $fillable = [
        'command',
        'input',
        'status',
        'is_queued',
        'progress_current',
        'progress_total',
        'message',
        'output',
        'exit_code',
        'error',
        'created_by',
        'started_at',
        'finished_at',
    ];

    protected $casts = [
        'input' => 'array',
        'is_queued' => 'boolean',
        'progress_current' => 'integer',
        'progress_total' => 'integer',
        'exit_code' => 'integer',
        'created_by' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isTerminal(): bool
    {
        return in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_FAILED], true);
    }

    /**
     * Percent for the progress bar, or null when the command never reported a
     * total — the UI shows an indeterminate bar rather than a fake 0%.
     */
    public function progressPercent(): ?int
    {
        if ($this->progress_total <= 0) {
            return null;
        }

        return (int) floor(min($this->progress_current, $this->progress_total) / $this->progress_total * 100);
    }

    public function durationSeconds(): ?int
    {
        if ($this->started_at === null) {
            return null;
        }

        // Carbon 3 returns a float here.
        return (int) $this->started_at->diffInSeconds($this->finished_at ?? now());
    }
}
