<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\DTOs\CreateMediaEpisodeData;
use App\Domain\Media\DTOs\UpdateMediaEpisodeData;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Repositories\MediaEpisodeRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MediaEpisodeService extends BaseService
{
    public function __construct(
        MediaEpisodeRepositoryInterface $repository
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

        $episode->incrementViews();

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

    public function createEpisode(CreateMediaEpisodeData $data): MediaEpisode
    {
        /** @var MediaEpisode $episode */
        $episode = $this->repository->create([
            'program_id' => $data->programId,
            'season_number' => $data->seasonNumber,
            'episode_number' => $data->episodeNumber,
            'slug' => $data->slug,
            'video_url' => $data->videoUrl,
            'duration_seconds' => $data->durationSeconds,
            'is_featured' => $data->isFeatured,
            'views_count' => 0,
        ]);

        return $episode;
    }

    /**
     * @throws ResourceNotFoundException
     */
    public function updateEpisode(int $id, UpdateMediaEpisodeData $data): MediaEpisode
    {
        $updated = $this->repository->update($id, [
            'season_number' => $data->seasonNumber,
            'episode_number' => $data->episodeNumber,
            'slug' => $data->slug,
            'video_url' => $data->videoUrl,
            'duration_seconds' => $data->durationSeconds,
            'is_featured' => $data->isFeatured,
        ]);

        if (! $updated) {
            throw new ResourceNotFoundException("Episode not found: {$id}");
        }

        $episode = $this->repository->find($id);
        $this->repository->clearCacheForProgram($episode->program_id);

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

        return true;
    }
}
