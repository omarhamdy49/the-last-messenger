<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Enums\ProgramStatus;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Repositories\MediaProgramRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class MediaProgramService extends BaseService
{
    /**
     * MediaProgramService constructor
     */
    public function __construct(
        MediaProgramRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get all programs with pagination
     */
    public function getAllPrograms(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, ['*'], ['category']);
    }

    /**
     * Get program by slug
     *
     * @throws ResourceNotFoundException
     */
    public function getProgramBySlug(string $slug): MediaProgram
    {
        $program = $this->repository->findBySlug($slug);

        if (! $program) {
            throw new ResourceNotFoundException("Program not found: {$slug}");
        }

        return $program;
    }

    /**
     * Get programs by category
     */
    public function getProgramsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage);
    }

    /**
     * Get programs by status
     */
    public function getProgramsByStatus(ProgramStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByStatus($status, $perPage);
    }

    /**
     * Get popular programs
     */
    public function getPopularPrograms(int $limit = 10): Collection
    {
        return $this->repository->getPopular($limit);
    }

    /**
     * Get recent programs
     */
    public function getRecentPrograms(int $limit = 10): Collection
    {
        return $this->repository->getRecent($limit);
    }

    /**
     * Search programs
     */
    public function searchPrograms(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Create a new program
     */
    public function createProgram(array $data): MediaProgram
    {
        $data['total_episodes'] = 0;
        $data['total_views'] = 0;
        $data['status'] = $data['status'] ?? ProgramStatus::ONGOING->value;

        return $this->repository->create($data);
    }

    /**
     * Update a program
     *
     * @throws ResourceNotFoundException
     */
    public function updateProgram(int $id, array $data): MediaProgram
    {
        $updated = $this->repository->update($id, $data);

        if (! $updated) {
            throw new ResourceNotFoundException("Program not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return $this->repository->find($id);
    }

    /**
     * Delete a program
     *
     * @throws ResourceNotFoundException
     */
    public function deleteProgram(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (! $deleted) {
            throw new ResourceNotFoundException("Program not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return true;
    }

    /**
     * Update program status
     *
     * @throws ResourceNotFoundException
     */
    public function updateProgramStatus(int $id, ProgramStatus|string $status): MediaProgram
    {
        $statusValue = $status instanceof ProgramStatus ? $status->value : $status;

        return $this->updateProgram($id, ['status' => $statusValue]);
    }

    /**
     * Get program with episode
     *
     * @return array{program: MediaProgram, episode: MediaEpisode}
     *
     * @throws ResourceNotFoundException
     */
    public function getProgramWithEpisode(string $programSlug, int $episodeNumber): array
    {
        $program = $this->getProgramBySlug($programSlug);

        $episode = $program->episodes()
            ->where('episode_number', $episodeNumber)
            ->published()
            ->first();

        if (! $episode) {
            throw new ResourceNotFoundException("Episode {$episodeNumber} not found for program: {$programSlug}");
        }

        // Increment views
        $episode->incrementViews();

        return [
            'program' => $program,
            'episode' => $episode,
        ];
    }

    /**
     * Filter programs by category
     */
    public function filterByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage);
    }

    /**
     * Filter programs by status
     */
    public function filterByStatus(string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByStatus($status, $perPage);
    }

    /**
     * Filter programs by published status
     */
    public function filterByPublished(bool $published, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, ['*'], ['category']);
    }

    /**
     * Filter programs by view count range
     */
    public function filterByViewCount(int $min, int $max, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByViewCount($min, $max, $perPage);
    }

    /**
     * Filter programs by episode count range
     */
    public function filterByEpisodeCount(int $min, int $max, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByEpisodeCount($min, $max, $perPage);
    }

    /**
     * Update episode count for a program
     */
    public function updateEpisodeCount(int $programId): void
    {
        $this->repository->updateEpisodeCount($programId);
    }

    /**
     * Filter programs with multiple criteria
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->filterPrograms($filters, $perPage, ['category']);
    }
}
