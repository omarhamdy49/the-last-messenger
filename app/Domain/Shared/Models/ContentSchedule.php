<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Enums\ScheduleAction;
use App\Domain\Shared\Enums\ScheduleStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class ContentSchedule extends Model
{
    use SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'schedulable_type',
        'schedulable_id',
        'scheduled_at',
        'action',
        'status',
        'timezone',
        'recurrence_type',
        'recurrence_interval',
        'recurrence_ends_at',
        'recurrence_days',
        'scheduled_data',
        'executed_at',
        'error_message',
        'retry_count',
        'next_retry_at',
        'created_by',
        'notes',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'scheduled_at' => 'datetime',
        'executed_at' => 'datetime',
        'recurrence_ends_at' => 'datetime',
        'next_retry_at' => 'datetime',
        'scheduled_data' => 'array',
        'recurrence_days' => 'array',
        'action' => ScheduleAction::class,
        'status' => ScheduleStatus::class,
        'retry_count' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Get the schedulable content (polymorphic relationship)
     */
    public function schedulable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Get the user who created this schedule
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope to get pending schedules
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopePending($query)
    {
        return $query->where('status', ScheduleStatus::PENDING);
    }

    /**
     * Scope to get due schedules (ready to execute)
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeDue($query)
    {
        return $query->where('status', ScheduleStatus::PENDING)
            ->where('scheduled_at', '<=', now());
    }

    /**
     * Scope to filter by action
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeByAction($query, ScheduleAction|string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to filter by status
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeByStatus($query, ScheduleStatus|string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to filter by schedulable type
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeByType($query, string $type)
    {
        return $query->where('schedulable_type', $type);
    }

    /**
     * Scope to get recurring schedules
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeRecurring($query)
    {
        return $query->whereNotNull('recurrence_type');
    }

    /**
     * Scope to get one-time schedules
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeOneTime($query)
    {
        return $query->whereNull('recurrence_type');
    }

    /**
     * Scope to get failed schedules that can be retried
     *
     * @param  mixed  $query
     * @return mixed
     */
    public function scopeRetryable($query)
    {
        return $query->where('status', ScheduleStatus::FAILED)
            ->where('retry_count', '<', 3)
            ->where(function ($q) {
                $q->whereNull('next_retry_at')
                    ->orWhere('next_retry_at', '<=', now());
            });
    }

    /**
     * Mark schedule as processing
     */
    public function markAsProcessing(): void
    {
        $this->update([
            'status' => ScheduleStatus::PROCESSING,
        ]);
    }

    /**
     * Mark schedule as published
     */
    public function markAsPublished(): void
    {
        $this->update([
            'status' => ScheduleStatus::PUBLISHED,
            'executed_at' => now(),
            'error_message' => null,
        ]);
    }

    /**
     * Mark schedule as failed
     */
    public function markAsFailed(string $errorMessage): void
    {
        $this->update([
            'status' => ScheduleStatus::FAILED,
            'error_message' => $errorMessage,
            'retry_count' => $this->retry_count + 1,
            'next_retry_at' => now()->addMinutes(5 * ($this->retry_count + 1)), // Exponential backoff
        ]);
    }

    /**
     * Mark schedule as cancelled
     */
    public function markAsCancelled(): void
    {
        $this->update([
            'status' => ScheduleStatus::CANCELLED,
        ]);
    }

    /**
     * Check if schedule is due for execution
     */
    public function isDue(): bool
    {
        return $this->status === ScheduleStatus::PENDING
            && $this->scheduled_at <= now();
    }

    /**
     * Check if schedule can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [ScheduleStatus::PENDING, ScheduleStatus::FAILED]);
    }

    /**
     * Check if schedule can be updated
     */
    public function canBeUpdated(): bool
    {
        return $this->status === ScheduleStatus::PENDING;
    }

    /**
     * Check if schedule is recurring
     */
    public function isRecurring(): bool
    {
        return ! is_null($this->recurrence_type);
    }

    /**
     * Get formatted scheduled time in user timezone
     */
    public function getFormattedScheduledAtAttribute(): string
    {
        return $this->scheduled_at
            ->timezone($this->timezone)
            ->format('Y-m-d H:i T');
    }

    /**
     * Get content title for display
     */
    public function getContentTitleAttribute(): string
    {
        if (! $this->schedulable) {
            return 'Content not found';
        }

        // Try to get title from different possible attributes
        if (method_exists($this->schedulable, 'getTitle')) {
            return $this->schedulable->getTitle();
        }

        if (isset($this->schedulable->title)) {
            $title = $this->schedulable->title;

            return is_array($title) ? ($title['ar'] ?? $title['en'] ?? 'Untitled') : $title;
        }

        if (isset($this->schedulable->name)) {
            $name = $this->schedulable->name;

            return is_array($name) ? ($name['ar'] ?? $name['en'] ?? 'Unnamed') : $name;
        }

        return 'Content #'.$this->schedulable_id;
    }

    /**
     * Get short content type name
     */
    public function getContentTypeNameAttribute(): string
    {
        $typeMap = [
            'App\\Domain\\Blog\\Models\\Article' => 'مقال',
            'App\\Domain\\Hadith\\Models\\Hadith' => 'حديث',
            'App\\Domain\\Media\\Models\\MediaProgram' => 'برنامج',
            'App\\Domain\\Media\\Models\\MediaEpisode' => 'حلقة',
            'App\\Domain\\Seerah\\Models\\SeerahEvent' => 'حدث سيرة',
            'App\\Domain\\Sahaba\\Models\\Sahabi' => 'صحابي',
            'App\\Domain\\Quran\\Models\\QuranVerse' => 'آية',
            'App\\Domain\\Quran\\Models\\QuranSurah' => 'سورة',
        ];

        return $typeMap[$this->schedulable_type] ?? class_basename($this->schedulable_type);
    }
}
