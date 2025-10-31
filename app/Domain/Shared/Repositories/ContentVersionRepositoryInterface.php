<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Contracts\RepositoryInterface;
use App\Domain\Shared\Models\ContentVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Content Version Repository Interface
 */
interface ContentVersionRepositoryInterface extends RepositoryInterface
{
    /**
     * Get versions for a specific model.
     */
    public function getVersionsForModel(Model $model, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get a specific version.
     */
    public function getVersion(int $versionId): ?ContentVersion;

    /**
     * Get versions by change type.
     */
    public function getVersionsByType(string $type, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get versions in date range.
     */
    public function getVersionsInRange(
        $startDate,
        $endDate,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator;

    /**
     * Get latest version for a model.
     */
    public function getLatestVersion(Model $model): ?ContentVersion;

    /**
     * Get version by number for a model.
     */
    public function getVersionByNumber(Model $model, int $versionNumber): ?ContentVersion;

    /**
     * Get version timeline for a model.
     */
    public function getVersionTimeline(Model $model): Collection;

    /**
     * Delete old versions (keep last N versions).
     */
    public function pruneOldVersions(Model $model, int $keepCount = 10): int;

    /**
     * Get versions changed by user.
     */
    public function getVersionsByUser(int $userId, int $perPage = 15): LengthAwarePaginator;

    /**
     * Get version statistics.
     */
    public function getVersionStats(): array;
}
