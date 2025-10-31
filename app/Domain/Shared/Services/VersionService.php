<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use App\Domain\Shared\Models\ContentVersion;
use App\Domain\Shared\Repositories\ContentVersionRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Version Service
 *
 * Handles all version control operations
 */
class VersionService
{
    public function __construct(
        protected ContentVersionRepositoryInterface $repository
    ) {}

    /**
     * Create a new version for a model.
     *
     * @param  array<string, mixed>  $changes
     * @param  array<string, mixed>|null  $metadata
     */
    public function createVersion(
        Model $model,
        array $changes,
        ?int $userId = null,
        ?string $summary = null,
        ?array $metadata = null
    ): ContentVersion {
        try {
            $latestVersion = $this->repository->getLatestVersion($model);
            $newVersionNumber = ($latestVersion?->version_number ?? 0) + 1;

            $diff = [];
            if ($latestVersion && $latestVersion->snapshot) {
                $diff = $this->calculateDiff($latestVersion->snapshot, $changes);
            }

            $version = new ContentVersion([
                'versionable_type' => get_class($model),
                'versionable_id' => $model->id,
                'version_number' => $newVersionNumber,
                'change_type' => $this->determineChangeType($model, $newVersionNumber),
                'changed_by' => $userId ?? Auth::id(),
                'change_summary' => $summary ?? "Version {$newVersionNumber}",
                'diff' => $diff,
                'snapshot' => $changes,
                'metadata' => $metadata,
                'ip_address' => request()?->ip(),
                'user_agent' => request()?->userAgent(),
            ]);

            $version->save();

            Log::info('Version created', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'version' => $newVersionNumber,
            ]);

            return $version;
        } catch (\Exception $e) {
            Log::error('Failed to create version', [
                'model' => get_class($model),
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }

    /**
     * Get all versions for a model.
     */
    public function getVersions(Model $model, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getVersionsForModel($model, $perPage);
    }

    /**
     * Get a specific version by ID.
     */
    public function getVersion(int $versionId): ?ContentVersion
    {
        return $this->repository->getVersion($versionId);
    }

    /**
     * Get version timeline for a model.
     */
    public function getVersionTimeline(Model $model): Collection
    {
        return $this->repository->getVersionTimeline($model);
    }

    /**
     * Compare two versions.
     *
     * @return array<string, array<string, mixed>>
     */
    public function compareVersions(int $version1Id, int $version2Id): array
    {
        $v1 = $this->repository->getVersion($version1Id);
        $v2 = $this->repository->getVersion($version2Id);

        if (! $v1 || ! $v2) {
            return [];
        }

        return $this->calculateDiff($v1->snapshot ?? [], $v2->snapshot ?? []);
    }

    /**
     * Revert a model to a specific version.
     */
    public function revertToVersion(Model $model, int $versionId, ?int $userId = null): bool
    {
        DB::beginTransaction();

        try {
            $version = $this->repository->getVersion($versionId);

            if (! $version || ! $version->snapshot) {
                throw new \Exception('Version not found or has no snapshot');
            }

            // Update model with snapshot data
            $model->fill($version->snapshot);
            $model->save();

            // Create a new version for the revert action
            $this->createVersion(
                $model,
                $version->snapshot,
                $userId,
                "Reverted to version {$version->version_number}",
                ['reverted_from' => $version->version_number]
            );

            DB::commit();

            Log::info('Model reverted to version', [
                'model' => get_class($model),
                'model_id' => $model->id,
                'version' => $version->version_number,
            ]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to revert version', [
                'model' => get_class($model),
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Publish a specific version (mark as published).
     */
    public function publishVersion(int $versionId, ?int $userId = null): bool
    {
        try {
            $version = $this->repository->getVersion($versionId);

            if (! $version) {
                return false;
            }

            // Update change type to published
            DB::table('content_versions')
                ->where('id', $versionId)
                ->update(['change_type' => 'published']);

            Log::info('Version published', [
                'version_id' => $versionId,
                'user_id' => $userId,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to publish version', [
                'version_id' => $versionId,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Get changes for a model in a date range.
     */
    public function getChanges(Model $model, $dateFrom, $dateTo): Collection
    {
        return $this->repository->getVersionsInRange($dateFrom, $dateTo)
            ->getCollection()
            ->where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id);
    }

    /**
     * Get current version number for a model.
     */
    public function getCurrentVersion(Model $model): int
    {
        $latestVersion = $this->repository->getLatestVersion($model);

        return $latestVersion?->version_number ?? 0;
    }

    /**
     * Get version statistics.
     *
     * @return array<string, mixed>
     */
    public function getVersionStats(): array
    {
        return $this->repository->getVersionStats();
    }

    /**
     * Prune old versions (keep last N versions).
     */
    public function pruneOldVersions(Model $model, int $keepCount = 10): int
    {
        return $this->repository->pruneOldVersions($model, $keepCount);
    }

    /**
     * Get versions by user.
     */
    public function getVersionsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getVersionsByUser($userId, $perPage);
    }

    /**
     * Calculate diff between two arrays.
     *
     * @param  array<string, mixed>  $old
     * @param  array<string, mixed>  $new
     * @return array<string, array<string, mixed>>
     */
    protected function calculateDiff(array $old, array $new): array
    {
        $diff = [];

        // Find changed and new keys
        foreach ($new as $key => $value) {
            $oldValue = $old[$key] ?? null;

            if ($oldValue !== $value) {
                $diff[$key] = [
                    'old' => $oldValue,
                    'new' => $value,
                    'type' => $oldValue === null ? 'added' : 'modified',
                ];
            }
        }

        // Find deleted keys
        foreach ($old as $key => $value) {
            if (! isset($new[$key])) {
                $diff[$key] = [
                    'old' => $value,
                    'new' => null,
                    'type' => 'deleted',
                ];
            }
        }

        return $diff;
    }

    /**
     * Determine the change type based on model state.
     */
    protected function determineChangeType(Model $model, int $versionNumber): string
    {
        if ($versionNumber === 1) {
            return 'created';
        }

        if ($model->wasRecentlyCreated) {
            return 'created';
        }

        if (method_exists($model, 'trashed') && $model->trashed()) {
            return 'deleted';
        }

        return 'updated';
    }

    /**
     * Get version diff in human-readable format.
     *
     * @return array<string, string>
     */
    public function getVersionDiffFormatted(int $versionId): array
    {
        $version = $this->repository->getVersion($versionId);

        if (! $version || ! $version->diff) {
            return [];
        }

        $formatted = [];

        foreach ($version->diff as $field => $changes) {
            $type = $changes['type'] ?? 'modified';
            $old = $changes['old'] ?? null;
            $new = $changes['new'] ?? null;

            $formatted[$field] = match ($type) {
                'added' => "Added: {$new}",
                'deleted' => "Deleted: {$old}",
                'modified' => "Changed from '{$old}' to '{$new}'",
                default => "Changed",
            };
        }

        return $formatted;
    }
}
