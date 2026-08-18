<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\UpdateMediaEpisodeData;
use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

/**
 * @extends BaseService<MediaEpisodeRepositoryInterface>
 */
class MediaEpisodeService extends BaseService
{
    public function __construct(
        MediaEpisodeRepositoryInterface $repository,
        private readonly MediaProgramRepositoryInterface $programRepository,
        private readonly MediaViewRecorder $viewRecorder,
    ) {
        $this->repository = $repository;
    }

    public function paginateByProgram(int $programId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->repository->paginateByProgram($programId, $perPage);
    }

    public function getByProgram(int $programId): Collection
    {
        return $this->repository->getByProgram($programId);
    }

    public function paginateEpisodes(
        int $perPage = 20,
        ?int $programId = null,
        ?string $search = null,
        ?int $excludeProgramId = null,
        string $sortBy = 'created_at',
        string $sortDir = 'desc',
        ?string $status = null
    ): LengthAwarePaginator {
        return $this->repository->paginateEpisodes($perPage, $programId, $search, $excludeProgramId, $sortBy, $sortDir, $status);
    }

    /**
     * Publish every episode whose scheduled publish_at has passed.
     *
     * @return int Number of episodes published
     */
    public function publishDueEpisodes(): int
    {
        return $this->repository->publishDue();
    }

    /**
     * @return array{total:int,featured:int,total_views:int,total_duration_seconds:int}
     */
    public function getIndexStats(?int $programId = null): array
    {
        return $this->repository->getIndexStats($programId);
    }

    /**
     * Renumber a program's episodes to the given id order (1-based).
     *
     * @param  list<int>  $orderedIds
     */
    public function reorderEpisodes(int $programId, array $orderedIds): void
    {
        $this->repository->reorderForProgram($programId, $orderedIds);
    }

    /**
     * Move episodes from other programs into the target program.
     *
     * @param  list<int>  $episodeIds
     * @return int Number of episodes moved
     */
    public function assignEpisodes(int $programId, array $episodeIds): int
    {
        return $this->repository->assignToProgram($programId, $episodeIds);
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function getEpisode(int $programId, int $season, int $episodeNumber): MediaEpisode
    {
        $episode = $this->repository->findByNumber($programId, $season, $episodeNumber);

        if (! $episode) {
            throw new ResourceNotFoundException(
                "Episode S{$season}E{$episodeNumber} not found for program {$programId}"
            );
        }

        // Queued + deduplicated; replaces the old synchronous incrementViews().
        $this->viewRecorder->record(MediaViewableType::Episode, (int) $episode->id);

        return $episode;
    }

    public function findNextEpisode(MediaEpisode $episode): ?MediaEpisode
    {
        return $this->repository->findNext($episode);
    }

    /**
     * In-progress episodes for a user's "Continue Watching" row.
     */
    public function getContinueWatching(int $userId, int $limit = 10): Collection
    {
        return $this->repository->getContinueWatching($userId, $limit);
    }

    /**
     * A user's watch positions for a set of episodes, keyed by episode id.
     *
     * @param  list<int>  $episodeIds
     * @return array<int, array{seconds: int, completed: bool}>
     */
    public function getProgressForEpisodes(int $userId, array $episodeIds): array
    {
        return $this->repository->getProgressForEpisodes($userId, $episodeIds);
    }

    /**
     * Upsert the authenticated user's watch position for an episode.
     * $userId always comes from auth — never from request input.
     *
     * @throws ResourceNotFoundException when the episode is not publicly visible,
     *                                   so drafts stay invisible to API clients.
     */
    public function saveProgress(int $userId, int $episodeId, int $seconds, bool $completed = false): void
    {
        if (! $this->repository->publicEpisodeExists($episodeId)) {
            throw new ResourceNotFoundException("Episode not found: {$episodeId}");
        }

        $this->repository->saveWatchProgress($userId, $episodeId, $seconds, $completed);
    }

    public function createEpisode(CreateMediaEpisodeData $data): MediaEpisode
    {
        /** @var MediaEpisode $episode */
        $episode = $this->repository->create([
            'program_id' => $data->programId,
            'season_number' => $data->seasonNumber,
            'episode_number' => $data->episodeNumber,
            'slug' => $data->slug ?? $this->generateSlug($data->programId, $data->seasonNumber, $data->episodeNumber),
            'video_url' => $data->videoUrl,
            'duration_seconds' => $data->durationSeconds,
            'is_featured' => $data->isFeatured,
            'is_published' => $data->isPublished,
            'publish_at' => $data->publishAt,
            'views_count' => 0,
        ]);

        return $episode;
    }

    /**
     * Default episode slug: "{program_slug}-s{season}e{episode}" (e.g. "el-hesn-s1e2"),
     * with a numeric suffix when that slug is already taken by another episode.
     */
    private function generateSlug(int $programId, int $seasonNumber, int $episodeNumber): string
    {
        /** @var MediaProgram|null $program */
        $program = $this->programRepository->find($programId, ['id', 'slug']);
        $base = Str::slug(($program?->slug ?? 'episode')."-s{$seasonNumber}e{$episodeNumber}");

        $candidate = $base;
        for ($suffix = 2; $this->repository->slugExists($candidate); $suffix++) {
            $candidate = "{$base}-{$suffix}";
        }

        return $candidate;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function updateEpisode(int $id, UpdateMediaEpisodeData $data): MediaEpisode
    {
        $previous = $this->repository->find($id);

        $updated = $this->repository->update($id, [
            'season_number' => $data->seasonNumber,
            'episode_number' => $data->episodeNumber,
            'slug' => $data->slug,
            'video_url' => $data->videoUrl,
            'duration_seconds' => $data->durationSeconds,
            'is_featured' => $data->isFeatured,
            'is_published' => $data->isPublished,
            'publish_at' => $data->publishAt,
        ]);

        if (! $updated) {
            throw new ResourceNotFoundException("Episode not found: {$id}");
        }

        $episode = $this->repository->find($id);
        $this->repository->clearCacheForProgram($episode->program_id);

        // The public episode page caches by (program, season, number) — drop
        // both the previous slot (numbers may have changed) and the current one.
        if ($previous !== null) {
            $this->repository->clearCacheForEpisodeNumber($previous->program_id, $previous->season_number, $previous->episode_number);
        }
        $this->repository->clearCacheForEpisodeNumber($episode->program_id, $episode->season_number, $episode->episode_number);

        return $episode;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function deleteEpisode(int $id): bool
    {
        $episode = $this->repository->find($id);

        if (! $episode) {
            throw new ResourceNotFoundException("Episode not found: {$id}");
        }

        $programId = $episode->program_id;

        $this->repository->delete($id);
        $this->repository->clearCacheForProgram($programId);
        $this->repository->clearCacheForEpisodeNumber($programId, $episode->season_number, $episode->episode_number);

        return true;
    }
}
