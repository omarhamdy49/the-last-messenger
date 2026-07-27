<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Media;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class MediaRepository
{
    public function getAllWithFilters(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = Media::query();

        if (! empty($filters['search'])) {
            $query->search((string) $filters['search']);
        }

        if (! empty($filters['type'])) {
            $query->ofType((string) $filters['type']);
        }

        if (! empty($filters['collection'])) {
            $query->inCollection((string) $filters['collection']);
        }

        if (! empty($filters['mime_type'])) {
            $query->byMimeType((string) $filters['mime_type']);
        }

        if (! empty($filters['tag'])) {
            $query->withTag($filters['tag']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        if (! empty($filters['size_min'])) {
            $query->where('size', '>=', (int) $filters['size_min']);
        }

        if (! empty($filters['size_max'])) {
            $query->where('size', '<=', (int) $filters['size_max']);
        }

        if (! empty($filters['unused'])) {
            $query->unused();
        }

        $this->applySorting($query, $filters);

        return $query->paginate($perPage)->withQueryString();
    }

    public function searchMedia(string $searchQuery, ?string $type = null, int $limit = 20): Collection
    {
        $query = Media::query()->search($searchQuery);

        if ($type) {
            $query->ofType($type);
        }

        return $query
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    public function getMediaUsage(int $mediaId): array
    {
        $media = Media::findOrFail($mediaId);

        $usage = [
            'total_count' => 0,
            'models' => [],
        ];

        if (! $media->mediable_type || ! $media->mediable_id) {
            return $usage;
        }

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
        } catch (\Throwable) {
            // Ignore broken morph references in usage reporting.
        }

        return $usage;
    }

    public function bulkDelete(array $ids): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($ids as $id) {
            try {
                $media = Media::query()->findOrFail($id);
                $media->delete();
                $results['success']++;
            } catch (\Throwable $exception) {
                $results['failed']++;
                $results['errors'][$id] = $exception->getMessage();
            }
        }

        return $results;
    }

    public function bulkUpdateTags(array $ids, array $tags): array
    {
        $results = [
            'success' => 0,
            'failed' => 0,
            'errors' => [],
        ];

        foreach ($ids as $id) {
            try {
                $media = Media::query()->findOrFail($id);
                $media->update(['tags' => array_values(array_unique($tags))]);
                $results['success']++;
            } catch (\Throwable $exception) {
                $results['failed']++;
                $results['errors'][$id] = $exception->getMessage();
            }
        }

        return $results;
    }

    public function findById(int $id): Media
    {
        return Media::query()->findOrFail($id);
    }

    public function findActiveHomeCardImage(int $id): ?Media
    {
        $isUsed = DB::table('home_module_cards')
            ->where('image', (string) $id)
            ->where('is_active', true)
            ->exists();

        if (! $isUsed) {
            return null;
        }

        return Media::query()
            ->select([
                'id',
                'public_key',
                'filename',
                'original_filename',
                'path',
                'mime_type',
                'disk',
                'metadata',
                'type',
            ])
            ->images()
            ->find($id);
    }

    public function create(array $data): Media
    {
        return Media::query()->create($data);
    }

    public function update(int $id, array $data): Media
    {
        $media = $this->findById($id);
        $media->update($data);

        return $media->fresh();
    }

    public function delete(int $id): bool
    {
        return $this->findById($id)->delete();
    }

    public function getAllCollections(): SupportCollection
    {
        return Media::query()
            ->select('collection_name')
            ->whereNotNull('collection_name')
            ->distinct()
            ->orderBy('collection_name')
            ->pluck('collection_name');
    }

    public function getAllTags(): array
    {
        return Media::query()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatten()
            ->filter()
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    public function getStatistics(): array
    {
        return [
            'total_count' => Media::query()->count(),
            'total_size' => Media::query()->sum('size'),
            'images_count' => Media::query()->images()->count(),
            'videos_count' => Media::query()->videos()->count(),
            'audio_count' => Media::query()->where('type', 'audio')->count(),
            'documents_count' => Media::query()->documents()->count(),
            'unused_count' => Media::query()->unused()->count(),
            'collections' => Media::query()
                ->select('collection_name', DB::raw('count(*) as count'))
                ->groupBy('collection_name')
                ->pluck('count', 'collection_name')
                ->toArray(),
        ];
    }

    protected function applySorting(Builder $query, array $filters): void
    {
        $allowedSorts = ['created_at', 'updated_at', 'size', 'filename', 'original_filename', 'type'];
        $sortField = in_array($filters['sort'] ?? 'created_at', $allowedSorts, true)
            ? $filters['sort']
            : 'created_at';
        $sortOrder = strtolower((string) ($filters['order'] ?? 'desc')) === 'asc' ? 'asc' : 'desc';

        $query->orderBy($sortField, $sortOrder);
    }

    private function getModelName(mixed $model): string
    {
        if (method_exists($model, 'getTitle')) {
            return $model->getTitle();
        }

        if (isset($model->title)) {
            return (string) $model->title;
        }

        if (isset($model->name)) {
            return (string) $model->name;
        }

        return class_basename($model).' #'.$model->id;
    }

    private function getModelUrl(mixed $model): ?string
    {
        $routes = [
            'Article' => 'admin.articles.show',
            'Sahabi' => 'admin.sahaba.show',
            'SeerahEvent' => 'admin.seerah-events.show',
            'Series' => 'admin.series.show',
        ];

        $modelClass = class_basename($model);

        if (! isset($routes[$modelClass])) {
            return null;
        }

        return route($routes[$modelClass], $model->id);
    }
}
