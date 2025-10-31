<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Audit Log Model
 *
 * Tracks all user actions and changes across the system
 *
 * @property int $id
 * @property int|null $user_id
 * @property string $action
 * @property string $subject_type
 * @property int $subject_id
 * @property string|null $description
 * @property array|null $old_values
 * @property array|null $new_values
 * @property array|null $metadata
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $url
 * @property string|null $method
 * @property \Illuminate\Support\Carbon $created_at
 */
class AuditLog extends Model
{
    public const UPDATED_AT = null; // No updated_at column

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'description',
        'old_values',
        'new_values',
        'metadata',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'user_id' => 'integer',
        'subject_id' => 'integer',
        'old_values' => 'array',
        'new_values' => 'array',
        'metadata' => 'array',
        'created_at' => 'datetime',
    ];

    /**
     * Get the user who performed the action.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    /**
     * Get the subject model (the audited entity).
     */
    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get audit logs for a specific user.
     */
    public function scopeForUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get audit logs for a specific model.
     */
    public function scopeForModel($query, Model $model)
    {
        return $query->where('subject_type', get_class($model))
            ->where('subject_id', $model->id);
    }

    /**
     * Scope to get audit logs by action.
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get audit logs in date range.
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get recent logs.
     */
    public function scopeRecent($query, int $limit = 50)
    {
        return $query->latest('created_at')->limit($limit);
    }

    /**
     * Get formatted action name.
     */
    public function getActionLabel(): string
    {
        return ucwords(str_replace('_', ' ', $this->action));
    }

    /**
     * Get changes summary.
     */
    public function getChangesSummary(): array
    {
        $changes = [];

        if ($this->old_values && $this->new_values) {
            foreach ($this->new_values as $key => $newValue) {
                $oldValue = $this->old_values[$key] ?? null;
                if ($oldValue !== $newValue) {
                    $changes[$key] = [
                        'old' => $oldValue,
                        'new' => $newValue,
                    ];
                }
            }
        }

        return $changes;
    }

    /**
     * Check if this is a create action.
     */
    public function isCreate(): bool
    {
        return in_array($this->action, ['create', 'created', 'store']);
    }

    /**
     * Check if this is an update action.
     */
    public function isUpdate(): bool
    {
        return in_array($this->action, ['update', 'updated', 'edit']);
    }

    /**
     * Check if this is a delete action.
     */
    public function isDelete(): bool
    {
        return in_array($this->action, ['delete', 'deleted', 'destroy']);
    }
}
