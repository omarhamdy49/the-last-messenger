<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface MediaEpisodeRepositoryInterface extends RepositoryInterface
{
    /**
     * Paginated episodes for a given program, ordered by season+episode.
     */
    public function paginateByProgram(int $programId, int $perPage = 20): LengthAwarePaginator;

    /**
     * Admin list/picker query: optional program filter, title/slug search,
     * exclusion (episodes-library modal) and whitelisted sorting.
     */
    public function paginateEpisodes(
        int $perPage = 20,
        ?int $programId = null,
        ?string $search = null,
        ?int $excludeProgramId = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $status = null
    ): LengthAwarePaginator;

    /**
     * Publish every episode whose scheduled publish_at has passed.
     *
     * @return int Number of episodes published
     */
    public function publishDue(): int;

    /**
     * Aggregate counters for the admin index header (single query).
     *
     * @return array{total:int,featured:int,total_views:int,total_duration_seconds:int}
     */
    public function getIndexStats(?int $programId = null): array;

    /**
     * All episodes for a program (for small programs).
     */
    public function getByProgram(int $programId): Collection;

    /**
     * All episodes for an administrative playlist sync, including drafts.
     */
    public function getByProgramForSync(int $programId): Collection;

    /**
     * Whether an episode already uses the given slug.
     */
    public function slugExists(string $slug): bool;

    /**
     * Single episode by program + season + episode number.
     */
    public function findByNumber(int $programId, int $season, int $episodeNumber): ?MediaEpisode;

    /**
     * The episode that follows the given one (same program, next number or next season).
     */
    public function findNext(MediaEpisode $episode): ?MediaEpisode;

    /** Clear per-program episode cache. */
    public function clearCacheForProgram(int $programId): void;

    /** Clear the public per-number episode cache entry. */
    public function clearCacheForEpisodeNumber(int $programId, int $season, int $episodeNumber): void;

    /**
     * Renumber a program's episodes to match the given id order (1-based).
     * Ids not belonging to the program are ignored.
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderForProgram(int $programId, array $orderedIds): void;

    /**
     * Move episodes from other programs into the target program, appending
     * them to season 1 after the current highest episode number.
     *
     * @param  list<int>  $episodeIds
     * @return int Number of episodes moved
     */
    public function assignToProgram(int $programId, array $episodeIds): int;

    /**
     * In-progress (not completed) watch records for a user, most recent first.
     * Returns EpisodeWatchProgress models with episode + program translations loaded.
     */
    public function getContinueWatching(int $userId, int $limit = 10): Collection;
}
