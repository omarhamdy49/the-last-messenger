<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Enums\InteractionType;
use App\Domain\Shared\Models\UserInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/**
 * Has Interactions Trait
 * Provides interaction tracking functionality to any model
 */
trait HasInteractions
{
    /**
     * Get all interactions for this model
     */
    public function interactions(): MorphMany
    {
        return $this->morphMany(UserInteraction::class, 'interactable');
    }

    /**
     * Check if user has interacted with this content
     */
    public function hasInteraction(User $user, InteractionType|string $type): bool
    {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return $this->interactions()
            ->where('user_id', $user->id)
            ->where('interaction_type', $type)
            ->exists();
    }

    /**
     * Record an interaction
     *
     * @param  array<string, mixed>  $metadata
     */
    public function recordInteraction(
        User $user,
        InteractionType|string $type,
        ?array $metadata = null,
        ?int $durationSeconds = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?string $deviceType = null,
        ?string $platform = null
    ): UserInteraction {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return $this->interactions()->create([
            'user_id' => $user->id,
            'interaction_type' => $type,
            'metadata' => $metadata,
            'duration_seconds' => $durationSeconds,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'device_type' => $deviceType,
            'platform' => $platform,
        ]);
    }

    /**
     * Get interaction count by type
     */
    public function getInteractionCount(InteractionType|string $type): int
    {
        if ($type instanceof InteractionType) {
            $type = $type->value;
        }

        return $this->interactions()
            ->where('interaction_type', $type)
            ->count();
    }

    /**
     * Get total views count
     */
    public function getViewsCount(): int
    {
        return $this->getInteractionCount(InteractionType::VIEW);
    }

    /**
     * Get total likes count
     */
    public function getLikesCount(): int
    {
        return $this->getInteractionCount(InteractionType::LIKE);
    }

    /**
     * Get total shares count
     */
    public function getSharesCount(): int
    {
        return $this->getInteractionCount(InteractionType::SHARE);
    }

    /**
     * Get total bookmarks count
     */
    public function getBookmarksCount(): int
    {
        return $this->getInteractionCount(InteractionType::BOOKMARK);
    }

    /**
     * Get total comments count
     */
    public function getCommentsCount(): int
    {
        return $this->getInteractionCount(InteractionType::COMMENT);
    }

    /**
     * Get total downloads count
     */
    public function getDownloadsCount(): int
    {
        return $this->getInteractionCount(InteractionType::DOWNLOAD);
    }

    /**
     * Get unique users who interacted
     */
    public function getUniqueInteractorsCount(): int
    {
        return $this->interactions()
            ->distinct('user_id')
            ->count('user_id');
    }

    /**
     * Get engagement score for this content
     */
    public function getEngagementScore(): int
    {
        $score = 0;

        foreach (InteractionType::cases() as $type) {
            $count = $this->getInteractionCount($type);
            $score += $count * $type->getPoints();
        }

        return $score;
    }

    /**
     * Get most recent interaction
     */
    public function getLatestInteraction(): ?UserInteraction
    {
        return $this->interactions()->latest()->first();
    }

    /**
     * Get user's latest interaction with this content
     */
    public function getUserLatestInteraction(User $user): ?UserInteraction
    {
        return $this->interactions()
            ->where('user_id', $user->id)
            ->latest()
            ->first();
    }

    /**
     * Get interactions grouped by type
     *
     * @return array<string, int>
     */
    public function getInteractionsByType(): array
    {
        return $this->interactions()
            ->selectRaw('interaction_type, COUNT(*) as count')
            ->groupBy('interaction_type')
            ->pluck('count', 'interaction_type')
            ->toArray();
    }

    /**
     * Get average time spent on this content
     */
    public function getAverageTimeSpent(): ?int
    {
        return $this->interactions()
            ->whereNotNull('duration_seconds')
            ->avg('duration_seconds');
    }
}
