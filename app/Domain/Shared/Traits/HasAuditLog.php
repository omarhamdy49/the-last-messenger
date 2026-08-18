<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\AuditLog;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Auth;

/**
 * HasAuditLog Trait
 *
 * Add audit logging to any model
 *
 * Usage: use HasAuditLog in your model
 */
trait HasAuditLog
{
    /**
     * Boot the trait.
     */
    public static function bootHasAuditLog(): void
    {
        // Log creation
        static::created(function ($model) {
            if (method_exists($model, 'shouldAudit') && ! $model->shouldAudit()) {
                return;
            }

            $model->logAudit('created', 'Created new '.class_basename($model), null, $model->getAuditableAttributes());
        });

        // Log updates
        static::updated(function ($model) {
            if (method_exists($model, 'shouldAudit') && ! $model->shouldAudit()) {
                return;
            }

            // getChanges() is Eloquent's own dirty set. array_diff_assoc() over
            // raw attributes cannot be used: it compares by string, and an
            // enum-cast attribute is a BackedEnum instance, which fatals on
            // conversion. It also mis-reports arrays and dates as changed.
            if ($model->getChanges() !== []) {
                $model->logAudit(
                    'updated',
                    'Updated '.class_basename($model),
                    $model->getOriginal(),
                    $model->getAttributes(),
                );
            }
        });

        // Log deletion
        static::deleted(function ($model) {
            if (method_exists($model, 'shouldAudit') && ! $model->shouldAudit()) {
                return;
            }

            $model->logAudit('deleted', 'Deleted '.class_basename($model), $model->getAuditableAttributes(), null);
        });

        // Log restoration (if soft deletes)
        if (in_array(SoftDeletes::class, class_uses_recursive(static::class))) {
            static::restored(function ($model) {
                if (method_exists($model, 'shouldAudit') && ! $model->shouldAudit()) {
                    return;
                }

                $model->logAudit('restored', 'Restored '.class_basename($model), null, $model->getAuditableAttributes());
            });
        }
    }

    /**
     * Get all audit logs for this model.
     */
    public function auditLogs(): MorphMany
    {
        return $this->morphMany(AuditLog::class, 'subject')
            ->latest('created_at');
    }

    /**
     * Log an audit entry.
     */
    public function logAudit(
        string $action,
        ?string $description = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?array $metadata = null
    ): AuditLog {
        return $this->auditLogs()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'description' => $description ?? ucfirst($action).' '.class_basename($this),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
            'url' => request()?->fullUrl(),
            'method' => request()?->method(),
        ]);
    }

    /**
     * Get audit trail (all logs).
     */
    public function getAuditTrail(int $limit = 50)
    {
        return $this->auditLogs()->limit($limit)->get();
    }

    /**
     * Get recent audit logs.
     */
    public function getRecentAudits(int $limit = 10)
    {
        return $this->auditLogs()->limit($limit)->get();
    }

    /**
     * Get audit logs by action.
     */
    public function getAuditsByAction(string $action)
    {
        return $this->auditLogs()->where('action', $action)->get();
    }

    /**
     * Get audit logs in date range.
     */
    public function getAuditsInRange($startDate, $endDate)
    {
        return $this->auditLogs()
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get();
    }

    /**
     * Get auditable attributes (override in your model if needed).
     */
    protected function getAuditableAttributes(): array
    {
        // By default, get all attributes except timestamps
        $exclude = ['created_at', 'updated_at', 'deleted_at'];

        return collect($this->getAttributes())
            ->except($exclude)
            ->toArray();
    }

    /**
     * Get the last audit log.
     */
    public function getLastAudit(): ?AuditLog
    {
        return $this->auditLogs()->first();
    }

    /**
     * Check if model has audit logs.
     */
    public function hasAuditLogs(): bool
    {
        return $this->auditLogs()->exists();
    }

    /**
     * Get audit summary.
     */
    public function getAuditSummary(): array
    {
        return [
            'total_audits' => $this->auditLogs()->count(),
            'created_at' => $this->auditLogs()->where('action', 'created')->first()?->created_at,
            'last_updated_at' => $this->auditLogs()->where('action', 'updated')->first()?->created_at,
            'last_updated_by' => $this->auditLogs()->where('action', 'updated')->first()?->user,
            'total_updates' => $this->auditLogs()->where('action', 'updated')->count(),
        ];
    }
}
