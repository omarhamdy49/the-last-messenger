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

class MediaProgramService
{
    /**
     * MediaProgramService constructor
     *
     * @param MediaProgramRepositoryInterface $repository
     */
    public function __construct(
        private readonly MediaProgramRepositoryInterface $repository
    ) {
    }

    /**
     * Get all programs with pagination
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllPrograms(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, ['*'], ['category']);
    }

    /**
     * Get program by slug
     *
     * @param string $slug
     * @return MediaProgram
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
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProgramsByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByCategory($categoryId, $perPage);
    }

    /**
     * Get programs by status
     *
     * @param ProgramStatus|string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getProgramsByStatus(ProgramStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByStatus($status, $perPage);
    }

    /**
     * Get popular programs
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularPrograms(int $limit = 10): Collection
    {
        return $this->repository->getPopular($limit);
    }

    /**
     * Get recent programs
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentPrograms(int $limit = 10): Collection
    {
        return $this->repository->getRecent($limit);
    }

    /**
     * Search programs
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchPrograms(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Create a new program
     *
     * @param array $data
     * @return MediaProgram
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
     * @param int $id
     * @param array $data
     * @return MediaProgram
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
     * @param int $id
     * @return bool
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
     * @param int $id
     * @param ProgramStatus|string $status
     * @return MediaProgram
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
     * @param string $programSlug
     * @param int $episodeNumber
     * @return array{program: MediaProgram, episode: MediaEpisode}
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
}

