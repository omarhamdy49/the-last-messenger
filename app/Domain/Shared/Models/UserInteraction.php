<?php

declare(strict_types=1);

namespace App\Domain\Shared\Models;

use App\Domain\Shared\Enums\InteractionType;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * User Interaction Model
 * Tracks all user interactions with content across the platform
 *
 * @property int $id
 * @property int $user_id
 * @property int $interactable_id
 * @property string $interactable_type
 * @property InteractionType $interaction_type
 * @property array|null $metadata
 * @property int|null $duration_seconds
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property string|null $device_type
 * @property string|null $platform
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class UserInteraction extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'interactable_id',
        'interactable_type',
        'interaction_type',
        'metadata',
        'duration_seconds',
        'ip_address',
        'user_agent',
        'device_type',
        'platform',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'interaction_type' => InteractionType::class,
            'duration_seconds' => 'integer',
        ];
    }

    /**
     * Get the user who made the interaction
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the interactable item (polymorphic)
     */
    public function interactable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope: Filter by interaction type
     */
    public function scopeOfType(Builder $query, InteractionType|string $type): Builder
    {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return $query->where('interaction_type', $type);
    }

    /**
     * Scope: Views only
     */
    public function scopeViews(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::VIEW->value);
    }

    /**
     * Scope: Likes only
     */
    public function scopeLikes(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::LIKE->value);
    }

    /**
     * Scope: Shares only
     */
    public function scopeShares(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::SHARE->value);
    }

    /**
     * Scope: Bookmarks only
     */
    public function scopeBookmarks(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::BOOKMARK->value);
    }

    /**
     * Scope: Comments only
     */
    public function scopeComments(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::COMMENT->value);
    }

    /**
     * Scope: Downloads only
     */
    public function scopeDownloads(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::DOWNLOAD->value);
    }

    /**
     * Scope: Progress tracking
     */
    public function scopeProgress(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::PROGRESS->value);
    }

    /**
     * Scope: Completed items
     */
    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('interaction_type', InteractionType::COMPLETE->value);
    }

    /**
     * Scope: Filter by user
     */
    public function scopeByUser(Builder $query, int $userId): Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope: Filter by content type
     */
    public function scopeByContentType(Builder $query, string $contentType): Builder
    {
        return $query->where('interactable_type', $contentType);
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeBetweenDates(Builder $query, string $startDate, string $endDate): Builder
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    /**
     * Scope: Recent interactions
     */
    public function scopeRecent(Builder $query, int $days = 7): Builder
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Scope: Today's interactions
     */
    public function scopeToday(Builder $query): Builder
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Scope: This week's interactions
     */
    public function scopeThisWeek(Builder $query): Builder
    {
        return $query->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek(),
        ]);
    }

    /**
     * Scope: This month's interactions
     */
    public function scopeThisMonth(Builder $query): Builder
    {
        return $query->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year);
    }

    /**
     * Get the engagement points for this interaction
     */
    public function getPoints(): int
    {
        return $this->interaction_type->getPoints();
    }

    /**
     * Check if this interaction counts towards engagement score
     */
    public function countsTowardsEngagement(): bool
    {
        return $this->interaction_type->countsTowardsEngagement();
    }
}
