<?php

declare(strict_types=1);

namespace App\Domain\Media\Repositories;

use App\Domain\Media\Enums\ProgramStatus;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Shared\Repositories\BaseRepository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class MediaProgramRepository extends BaseRepository implements MediaProgramRepositoryInterface
{
    /**
     * Cache TTL in seconds (30 minutes)
     */
    private const CACHE_TTL = 1800;

    /**
     * {@inheritdoc}
     */
    protected function getModel(): Model
    {
        return new MediaProgram;
    }

    /**
     * Get programs by category
     */
    public function getByCategory(int $categoryId, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['category', 'episodes' => function ($query) {
                $query->published()->orderByDesc('episode_number')->limit(1);
            }])
            ->byCategory($categoryId)
            ->orderByDesc('total_views')
            ->paginate($perPage);
    }

    /**
     * Get programs by status
     */
    public function getByStatus(ProgramStatus|string $status, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['category'])
            ->byStatus($status)
            ->orderByDesc('created_at')
            ->paginate($perPage);
    }

    /**
     * Get popular programs
     */
    public function getPopular(int $limit = 10): Collection
    {
        return Cache::remember(
            "media_programs_popular_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with(['category'])
                ->popular($limit)
                ->get()
        );
    }

    /**
     * Get recent programs
     */
    public function getRecent(int $limit = 10): Collection
    {
        return Cache::remember(
            "media_programs_recent_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with(['category'])
                ->recent($limit)
                ->get()
        );
    }

    /**
     * Search programs
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['category'])
            ->where(function ($q) use ($query) {
                // Search in JSON title field (all languages)
                $q->whereRaw("JSON_SEARCH(title, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhereRaw("JSON_SEARCH(description, 'one', ?) IS NOT NULL", ["%{$query}%"]);
            })
            ->orderByDesc('total_views')
            ->paginate($perPage);
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $defaultRelations = ['category', 'episodes' => function ($query) {
            $query->published()->orderBy('episode_number');
        }];

        $relations = empty($relations) ? $defaultRelations : $relations;

        return Cache::remember(
            "media_program_slug_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($relations)
                ->where('slug', $slug)
                ->first($columns)
        );
    }

    /**
     * Clear cache for program
     */
    public function clearCacheForModel(int $id): void
    {
        $program = $this->find($id);
        if ($program) {
            Cache::forget("media_program_slug_{$program->slug}");
            Cache::forget('media_programs_popular_10');
            Cache::forget('media_programs_recent_10');
        }
    }
}
