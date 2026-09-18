<?php

declare(strict_types=1);

namespace App\Domain\Quran\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * The QF sync checkpoint for one canonical resources filter.
 *
 * @property int $id
 * @property string $resources_filter
 * @property string $filter_hash sha256 of resources_filter (unique key)
 * @property string|null $sync_token
 * @property int $last_sequence
 * @property Carbon|null $last_run_at
 * @property string $status idle|running|failed
 * @property string|null $last_error
 */
class SyncState extends Model
{
    public const STATUS_IDLE = 'idle';

    public const STATUS_RUNNING = 'running';

    public const STATUS_FAILED = 'failed';

    protected $table = 'quran_sync_states';

    protected $fillable = ['resources_filter', 'filter_hash', 'sync_token', 'last_sequence', 'last_run_at', 'status', 'last_error'];

    protected $hidden = ['sync_token'];

    protected $casts = [
        'last_sequence' => 'integer',
        'last_run_at' => 'datetime',
    ];

    public static function hashFilter(string $filter): string
    {
        return hash('sha256', $filter);
    }
}
