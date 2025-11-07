<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Media Repository
 *
 * Handles all database operations for media management
 */
class MediaRepository
{
    /**
     * Get all media with filters and pagination
     *
     * @param array $filters
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Media::query();

        // Search by keyword
        if (!empty($filters['search'])) {
            $query->search($filters['search']);
        }

        // Filter by type (image, video, document)
        if (!empty($filters['type'])) {
            $query->ofType($filters['type']);
        }

        // Filter by collection
        if (!empty($filters['collection'])) {
            $query->inCollection($filters['collection']);
        }

        // Filter by mime type
        if (!empty($filters['mime_type'])) {
            $query->byMimeType($filters['mime_type']);
        }

        // Filter by tag
        if (!empty($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        // Filter by date range
        if (!empty($filters['date_from']) && !empty($filters['date_to'])) {
            $query->createdBetween($filters['date_from'], $filters['date_to']);
        }

        // Filter by size range
        if (!empty($filters['size_min']) && !empty($filters['size_max'])) {
            $query->sizeBetween($filters['size_min'], $filters['size_max']);
        }

        // Filter unused media
        if (!empty($filters['unused'])) {
            $query->unused();
        }

        // Filter images only
        if (!empty($filters['images_only'])) {
            $query->images();
        }

        // Filter videos only
        if (!empty($filters['videos_only'])) {
            $query->videos();
        }

        // Filter documents only
        if (!empty($filters['documents_only'])) {
            $query->documents();
        }

        // Sorting
        $sortField = $filters['sort'] ?? 'created_at';
        $sortOrder = $filters['order'] ?? 'desc';
        $query->orderBy($sortField, $sortOrder);

        return $query->paginate($perPage);
    }

    /**
     * Search media by query and optional type
     *
     * @param string $searchQuery
     * @param string|null $type
     * @param int $limit
     * @return Collection
     */
    public function searchMedia(string $searchQuery, ?string $type = null, int $limit = 20): Collection
    {
        $query = Media::query();

        $query->search($searchQuery);

        if ($type) {
            $query->ofType($type);
        }

        return $query->limit($limit)->get();
    }

    /**
     * Get media usage information
     *
     * Returns where the media is being used across the application
     *
     * @param int $mediaId
     * @return array
     */
    public function getMediaUsage(int $mediaId): array
    {
        $media = Media::findOrFail($mediaId);

        $usage = [
            'total_count' => 0,
            'models' => [],
        ];

        if (!$media->mediable_type || !$media->mediable_id) {
            return $usage;
        }

        // Get the model using this media
        try {
            $model = $media->mediable;

            if ($model) {
                $usage['models'][] = [
                    'type' => $media->mediable_type,
                    'id' => $media->mediable_id,
                    'name' => $this->getModelName($model),
                    'url' => $this->getModelUrl($model),
                ];
                $usage['total_count'] = 1;
            }
        } catch (\Exception $e) {
            // Model doesn't exist anymore
        }

        return $usage;
    }

    /**
     * Get model name for display
     *
     * @param mixed $model
     * @return string
     */
    private function getModelName($model): string
    {
        if (method_exists($model, 'getTitle')) {
            return $model->getTitle();
        }

        if (isset($model->title)) {
            return $model->title;
        }

        if (isset($model->name)) {
            return $model->name;
        }

        return class_basename($model) . ' #' . $model->id;
    }

    /**
     * Get model URL for display
     *
     * @param mixed $model
     * @return string|null
     */
    private function getModelUrl($model): ?string
    {
        $modelClass = class_basename($model);

        $routes = [
            'Article' => 'admin.articles.show',
            'Sahabi' => 'admin.sahaba.show',
            'SeerahEvent' => 'admin.seerah-events.show',
            'Series' => 'admin.series.show',
        ];

        if (isset($routes[$modelClass])) {
            return route($routes[$modelClass], $model->id);
        }

        return null;
    }

    /**
     * Bulk delete media
     *
     * @param array $ids
     * @return array
     */
    public function bulkDelete(array $ids): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($ids as $id) {
            try {
                $media = Media::findOrFail($id);
                $media->delete();
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$id] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Bulk update tags
     *
     * @param array $ids
     * @param array $tags
     * @return array
     */
    public function bulkUpdateTags(array $ids, array $tags): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($ids as $id) {
            try {
                $media = Media::findOrFail($id);
                $media->update(['tags' => $tags]);
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][$id] = $e->getMessage();
            }
        }

        return $results;
    }

    /**
     * Get media by ID
     *
     * @param int $id
     * @return Media
     */
    public function findById(int $id): Media
    {
        return Media::findOrFail($id);
    }

    /**
     * Create new media record
     *
     * @param array $data
     * @return Media
     */
    public function create(array $data): Media
    {
        return Media::create($data);
    }

    /**
     * Update media record
     *
     * @param int $id
     * @param array $data
     * @return Media
     */
    public function update(int $id, array $data): Media
    {
        $media = Media::findOrFail($id);
        $media->update($data);
        return $media->fresh();
    }

    /**
     * Delete media
     *
     * @param int $id
     * @return bool
     */
    public function delete(int $id): bool
    {
        $media = Media::findOrFail($id);
        return $media->delete();
    }

    /**
     * Get all collections
     *
     * @return Collection
     */
    public function getAllCollections(): Collection
    {
        return Media::select('collection_name')
            ->distinct()
            ->whereNotNull('collection_name')
            ->orderBy('collection_name')
            ->pluck('collection_name');
    }

    /**
     * Get all tags
     *
     * @return array
     */
    public function getAllTags(): array
    {
        $tags = Media::whereNotNull('tags')
            ->get()
            ->pluck('tags')
            ->flatten()
            ->unique()
            ->sort()
            ->values()
            ->toArray();

        return $tags;
    }

    /**
     * Get media statistics
     *
     * @return array
     */
    public function getStatistics(): array
    {
        return [
            'total_count' => Media::count(),
            'total_size' => Media::sum('size'),
            'images_count' => Media::images()->count(),
            'videos_count' => Media::videos()->count(),
            'documents_count' => Media::documents()->count(),
            'unused_count' => Media::unused()->count(),
            'collections' => Media::select('collection_name', DB::raw('count(*) as count'))
                ->groupBy('collection_name')
                ->get()
                ->pluck('count', 'collection_name')
                ->toArray(),
        ];
    }

    /**
     * Get recently uploaded media
     *
     * @param int $limit
     * @return Collection
     */
    public function getRecentlyUploaded(int $limit = 10): Collection
    {
        return Media::orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get largest media files
     *
     * @param int $limit
     * @return Collection
     */
    public function getLargestFiles(int $limit = 10): Collection
    {
        return Media::orderBy('size', 'desc')
            ->limit($limit)
            ->get();
    }
}
