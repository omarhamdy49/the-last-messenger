<?php

declare(strict_types=1);

namespace App\Domain\Shared\Repositories;

use App\Domain\Shared\Models\ContentVersion;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Content Version Repository
 */
class ContentVersionRepository extends BaseRepository implements ContentVersionRepositoryInterface
{
    protected int $cacheTTL = 600; // 10 minutes

    /**
     * Get model instance.
     */
    protected function getModel(): Model
    {
        return new ContentVersion;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionsForModel(Model $model, int $perPage = 15): LengthAwarePaginator
    {
        return ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->with('changedBy:id,name,email')
            ->orderBy('version_number', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersion(int $versionId): ?ContentVersion
    {
        return Cache::remember(
            "content_version:{$versionId}",
            $this->cacheTTL,
            fn () => ContentVersion::with('changedBy:id,name,email')->find($versionId)
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionsByType(string $type, int $perPage = 15): LengthAwarePaginator
    {
        return ContentVersion::where('change_type', $type)
            ->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionsInRange(
        $startDate,
        $endDate,
        ?string $type = null,
        int $perPage = 15
    ): LengthAwarePaginator {
        $query = ContentVersion::whereBetween('created_at', [$startDate, $endDate]);

        if ($type) {
            $query->where('change_type', $type);
        }

        return $query->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getLatestVersion(Model $model): ?ContentVersion
    {
        return ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->orderBy('version_number', 'desc')
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionByNumber(Model $model, int $versionNumber): ?ContentVersion
    {
        return ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->where('version_number', $versionNumber)
            ->first();
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionTimeline(Model $model): Collection
    {
        return ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * {@inheritdoc}
     */
    public function pruneOldVersions(Model $model, int $keepCount = 10): int
    {
        $versionsToKeep = ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->orderBy('version_number', 'desc')
            ->limit($keepCount)
            ->pluck('id')
            ->toArray();

        $deleted = ContentVersion::where('versionable_type', get_class($model))
            ->where('versionable_id', $model->id)
            ->whereNotIn('id', $versionsToKeep)
            ->delete();

        $this->clearCache();

        return $deleted;
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionsByUser(int $userId, int $perPage = 15): LengthAwarePaginator
    {
        return ContentVersion::where('changed_by', $userId)
            ->with('changedBy:id,name,email')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * {@inheritdoc}
     */
    public function getVersionStats(): array
    {
        return Cache::remember('version_stats', $this->cacheTTL, function () {
            return [
                'total_versions' => ContentVersion::count(),
                'versions_today' => ContentVersion::whereDate('created_at', today())->count(),
                'versions_this_week' => ContentVersion::whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])->count(),
                'versions_this_month' => ContentVersion::whereMonth('created_at', now()->month)->count(),
                'by_type' => ContentVersion::select('change_type', DB::raw('count(*) as count'))
                    ->groupBy('change_type')
                    ->pluck('count', 'change_type')
                    ->toArray(),
                'most_active_users' => ContentVersion::select('changed_by', DB::raw('count(*) as count'))
                    ->whereNotNull('changed_by')
                    ->groupBy('changed_by')
                    ->orderBy('count', 'desc')
                    ->limit(10)
                    ->with('changedBy:id,name,email')
                    ->get()
                    ->map(fn ($v) => [
                        'user' => $v->changedBy?->name ?? 'Unknown',
                        'count' => $v->count,
                    ])
                    ->toArray(),
                'recent_versions' => ContentVersion::with('changedBy:id,name,email')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get()
                    ->map(fn ($v) => [
                        'id' => $v->id,
                        'type' => $v->change_type,
                        'summary' => $v->change_summary,
                        'user' => $v->changedBy?->name ?? 'System',
                        'created_at' => $v->created_at->diffForHumans(),
                    ])
                    ->toArray(),
            ];
        });
    }
}
