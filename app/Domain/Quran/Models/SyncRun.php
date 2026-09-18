<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use App\Domain\Quran\Enums\SyncRunMode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * An auditable record of one import/sync/fetch run.
 *
 * @property int $id
 * @property SyncRunMode $mode
 * @property string|null $resources_filter
 * @property Carbon $started_at
 * @property Carbon|null $finished_at
 * @property string $status running|succeeded|failed
 * @property int $mutations_applied
 * @property int $snapshots_fetched
 * @property int $rows_upserted
 * @property int $rows_deleted
 * @property int $rows_skipped_locked
 * @property list<array{key: string, reason: string}>|null $errors
 * @property int|null $triggered_by
 */
class SyncRun extends Model
{
    public const STATUS_RUNNING = 'running';

    public const STATUS_SUCCEEDED = 'succeeded';

    public const STATUS_FAILED = 'failed';

    /**
     * Counters a run may report.
     *
     * @var list<string>
     */
    public const COUNTERS = ['mutations_applied', 'snapshots_fetched', 'rows_upserted', 'rows_deleted', 'rows_skipped_locked'];

    protected $table = 'quran_sync_runs';

    protected $fillable = [
        'mode', 'resources_filter', 'started_at', 'finished_at', 'status', 'mutations_applied', 'snapshots_fetched',
        'rows_upserted', 'rows_deleted', 'rows_skipped_locked', 'errors', 'triggered_by',
    ];

    protected $casts = [
        'mode' => SyncRunMode::class,
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
        'mutations_applied' => 'integer',
        'snapshots_fetched' => 'integer',
        'rows_upserted' => 'integer',
        'rows_deleted' => 'integer',
        'rows_skipped_locked' => 'integer',
        'errors' => 'array',
        'triggered_by' => 'integer',
    ];

    /**
     * @param  array<string, int>  $counts  keys from COUNTERS
     * @param  list<array{key: string, reason: string}>  $errors
     */
    public function finish(array $counts, array $errors): void
    {
        $this->fill(array_intersect_key($counts, array_flip(self::COUNTERS)));
        $this->errors = $errors === [] ? null : $errors;
        $this->status = $errors === [] ? self::STATUS_SUCCEEDED : self::STATUS_FAILED;
        $this->finished_at = now();
        $this->save();
    }
}
