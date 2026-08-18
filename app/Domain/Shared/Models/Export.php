<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Export Model
 * Tracks all export operations for auditing
 *
 * @property int $id
 * @property int $user_id
 * @property string $module
 * @property string $format
 * @property int $record_count
 * @property array|null $filters
 * @property string|null $file_path
 * @property string $file_name
 * @property int $file_size
 * @property string $status
 * @property Carbon|null $completed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class Export extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'module',
        'format',
        'record_count',
        'filters',
        'file_path',
        'file_name',
        'file_size',
        'status',
        'completed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'filters' => 'array',
        'completed_at' => 'datetime',
    ];

    /**
     * Get the user who created the export
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mark export as completed
     */
    public function markAsCompleted(string $filePath, int $fileSize): void
    {
        $this->update([
            'status' => 'completed',
            'file_path' => $filePath,
            'file_size' => $fileSize,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark export as failed
     */
    public function markAsFailed(): void
    {
        $this->update([
            'status' => 'failed',
        ]);
    }
}
