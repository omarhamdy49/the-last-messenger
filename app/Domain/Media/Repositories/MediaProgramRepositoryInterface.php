<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Enums\ProgramStatus;
use App\Domain\Shared\Contracts\RepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

interface MediaProgramRepositoryInterface extends RepositoryInterface
{
    /**
     * Get programs by category
     *
     * @param int $categoryId
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get programs by status
     *
     * @param ProgramStatus|string $status
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByStatus(ProgramStatus|string $status, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get popular programs
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopular(int $limit = 10): Collection;

    /**
     * Get recent programs
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecent(int $limit = 10): Collection;

    /**
     * Search programs
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator;
}

