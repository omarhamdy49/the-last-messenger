<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Support\Carbon;

/**
 * Content Version Model
 *
 * Tracks all versions of content with full snapshots and diffs
 *
 * @property int $id
 * @property string $versionable_type
 * @property int $versionable_id
 * @property int $version_number
 * @property string $change_type
 * @property int|null $changed_by
 * @property string|null $change_summary
 * @property array|null $diff
 * @property array|null $snapshot
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property Carbon $created_at
 */
class ContentVersion extends Model
{
    public const UPDATED_AT = null; // No updated_at column

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'versionable_type',
        'versionable_id',
        'version_number',
        'change_type',
        'changed_by',
        'change_summary',
        'diff',
        'snapshot',
        'metadata',
        'ip_address',
        'user_agent',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'version_number' => 'integer',
        'changed_by' => 'integer',
        'diff' => 'array',
        'snapshot' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the parent versionable model.
     */
    public function versionable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who made the change.
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /**
     * Scope to get versions for a specific model.
     */
    public function scopeForModel($query, Model $model)
    {
        return $query->where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id);
    }

    /**
     * Scope to get versions by type.
     */
    public function scopeByChangeType($query, string $type)
    {
        return $query->where('change_type', $type);
    }

    /**
     * Scope to get versions in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Get formatted change type.
     */
    public function getChangeTypeLabel(): string
    {
        return match ($this->change_type) {
            'created' => 'Created',
            'updated' => 'Updated',
            'published' => 'Published',
            'unpublished' => 'Unpublished',
            'deleted' => 'Deleted',
            'restored' => 'Restored',
            default => ucfirst($this->change_type),
        };
    }

    /**
     * Check if this is the latest version.
     */
    public function isLatest(): bool
    {
        $latestVersion = static::forModel($this->versionable)
            ->max('version_number');

        return $this->version_number === $latestVersion;
    }
}
