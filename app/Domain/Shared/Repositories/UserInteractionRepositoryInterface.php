<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Enums\InteractionType;
use App\Domain\Shared\Models\UserInteraction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * User Interaction Repository Interface
 */
interface UserInteractionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get interactions by user
     */
    public function getByUser(int $userId, ?int $limit = null): Collection;

    /**
     * Get interactions by content
     */
    public function getByContent(string $contentType, int $contentId): Collection;

    /**
     * Get interactions by type
     */
    public function getByType(InteractionType|string $type, ?int $limit = null): Collection;

    /**
     * Get interactions between dates
     */
    public function getBetweenDates(Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Get user's interactions with specific content
     */
    public function getUserContentInteractions(int $userId, string $contentType, int $contentId): Collection;

    /**
     * Check if user has specific interaction with content
     */
    public function hasInteraction(int $userId, string $contentType, int $contentId, InteractionType|string $type): bool;

    /**
     * Get recent interactions
     */
    public function getRecent(int $days = 7, ?int $limit = null): Collection;

    /**
     * Get today's interactions
     */
    public function getToday(): Collection;

    /**
     * Get this week's interactions
     */
    public function getThisWeek(): Collection;

    /**
     * Get this month's interactions
     */
    public function getThisMonth(): Collection;

    /**
     * Count interactions by type
     */
    public function countByType(InteractionType|string $type): int;

    /**
     * Count user's interactions
     */
    public function countByUser(int $userId): int;

    /**
     * Get most interacted content
     *
     * @return array<array<string, mixed>>
     */
    public function getMostInteractedContent(int $limit = 10): array;

    /**
     * Get interaction statistics for date range
     *
     * @return array<string, int>
     */
    public function getStatsBetweenDates(Carbon $startDate, Carbon $endDate): array;

    /**
     * Delete old interactions
     */
    public function deleteOlderThan(Carbon $date): int;

    /**
     * Create or update interaction (for deduplication)
     */
    public function createOrUpdate(array $data): UserInteraction;
}
