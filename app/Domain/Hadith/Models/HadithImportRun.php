<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Models;

use App\Domain\Hadith\Enums\ImportStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * One row per import attempt. Replaces the old `hadith_sync_logs`.
 *
 * `unmapped_grades` records every raw string the normaliser could not interpret
 * (FR-007) and is surfaced in the admin, so mapping rules improve without
 * re-reading 51k records.
 *
 * @property int $id
 * @property string $collection_code
 * @property string|null $source_path
 * @property ImportStatus $status
 * @property array<string, mixed>|null $counts
 * @property array<int, string>|null $unmapped_grades
 * @property string|null $error_message
 * @property \Carbon\Carbon|null $started_at
 * @property \Carbon\Carbon|null $completed_at
 */
class HadithImportRun extends Model
{
    use HasFactory;

    protected $table = 'hadith_import_runs';

    /** @var array<string> */
    protected $fillable = [
        'collection_code',
        'source_path',
        'status',
        'counts',
        'unmapped_grades',
        'error_message',
        'started_at',
        'completed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'status' => ImportStatus::class,
            'counts' => 'array',
            'unmapped_grades' => 'array',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }
}
