<?php

declare(strict_types=1);

namespace App\Domain\Shared\Traits;

use App\Domain\Shared\Models\ContentVersion;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\Auth;

/**
 * HasVersions Trait
 *
 * Add version tracking to any model
 *
 * Usage: use HasVersions in your model
 */
trait HasVersions
{
    /**
     * Boot the trait.
     */
    public static function bootHasVersions(): void
    {
        // Create initial version on model creation
        static::created(function ($model) {
            if (method_exists($model, 'shouldCreateVersion') && ! $model->shouldCreateVersion()) {
                return;
            }

            $model->createVersion('created', 'Initial version');
        });

        // Create version on model update
        static::updated(function ($model) {
            if (method_exists($model, 'shouldCreateVersion') && ! $model->shouldCreateVersion()) {
                return;
            }

            $model->createVersion('updated', 'Content updated');
        });

        // Create version on model deletion
        static::deleted(function ($model) {
            if (method_exists($model, 'shouldCreateVersion') && ! $model->shouldCreateVersion()) {
                return;
            }

            $model->createVersion('deleted', 'Content deleted');
        });
    }

    /**
     * Get all versions for this model.
     */
    public function versions(): MorphMany
    {
        return $this->morphMany(ContentVersion::class, 'versionable')
            ->orderBy('version_number', 'desc');
    }

    /**
     * Create a new version.
     */
    public function createVersion(string $changeType = 'updated', ?string $summary = null, ?array $metadata = null): ContentVersion
    {
        $latestVersion = $this->versions()->max('version_number') ?? 0;
        $newVersionNumber = $latestVersion + 1;

        // Get the diff by comparing with previous version
        $diff = [];
        $previousVersion = $this->versions()->where('version_number', $latestVersion)->first();

        if ($previousVersion && $previousVersion->snapshot) {
            $diff = $this->calculateDiff($previousVersion->snapshot, $this->getVersionableAttributes());
        }

        return $this->versions()->create([
            'version_number' => $newVersionNumber,
            'change_type' => $changeType,
            'changed_by' => Auth::id(),
            'change_summary' => $summary ?? "Version {$newVersionNumber}",
            'diff' => $diff,
            'snapshot' => $this->getVersionableAttributes(),
            'metadata' => $metadata,
            'ip_address' => request()?->ip(),
            'user_agent' => request()?->userAgent(),
        ]);
    }

    /**
     * Get the latest version.
     */
    public function latestVersion(): ?ContentVersion
    {
        return $this->versions()->first();
    }

    /**
     * Get a specific version.
     */
    public function getVersion(int $versionNumber): ?ContentVersion
    {
        return $this->versions()->where('version_number', $versionNumber)->first();
    }

    /**
     * Revert to a specific version.
     */
    public function revertToVersion(int $versionNumber): bool
    {
        $version = $this->getVersion($versionNumber);

        if (! $version || ! $version->snapshot) {
            return false;
        }

        // Update model with snapshot data
        $this->fill($version->snapshot);
        $this->save();

        // Create a new version for the revert action
        $this->createVersion('restored', "Reverted to version {$versionNumber}");

        return true;
    }

    /**
     * Compare two versions.
     */
    public function compareVersions(int $version1, int $version2): array
    {
        $v1 = $this->getVersion($version1);
        $v2 = $this->getVersion($version2);

        if (! $v1 || ! $v2) {
            return [];
        }

        return $this->calculateDiff($v1->snapshot ?? [], $v2->snapshot ?? []);
    }

    /**
     * Get versionable attributes (override in your model if needed).
     */
    protected function getVersionableAttributes(): array
    {
        // By default, get all attributes except timestamps and IDs
        $exclude = ['id', 'created_at', 'updated_at', 'deleted_at'];

        return collect($this->getAttributes())
            ->except($exclude)
            ->toArray();
    }

    /**
     * Calculate diff between two arrays.
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
                    'changed' => true,
                ];
            }
        }

        // Find deleted keys
        foreach ($old as $key => $value) {
            if (! isset($new[$key])) {
                $diff[$key] = [
                    'old' => $value,
                    'new' => null,
                    'deleted' => true,
                ];
            }
        }

        return $diff;
    }

    /**
     * Get version count.
     */
    public function getVersionCount(): int
    {
        return $this->versions()->count();
    }

    /**
     * Get current version number.
     */
    public function getCurrentVersionNumber(): int
    {
        return $this->versions()->max('version_number') ?? 1;
    }

    /**
     * Check if model has versions.
     */
    public function hasVersions(): bool
    {
        return $this->versions()->exists();
    }
}
