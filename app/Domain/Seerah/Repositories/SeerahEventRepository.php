<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Shared\Repositories\BaseRepository;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\EventType;
use App\Domain\Seerah\Models\SeerahEvent;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;

class SeerahEventRepository extends BaseRepository implements SeerahEventRepositoryInterface
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
        return new SeerahEvent();
    }

    /**
     * Get events by type
     *
     * @param EventType|string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['article', 'participants'])
            ->byType($type)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Hijri year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['article', 'participants'])
            ->byHijriYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Gregorian year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['article', 'participants'])
            ->byGregorianYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get featured events
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::remember(
            "seerah_featured_{$limit}",
            self::CACHE_TTL,
            fn() => $this->model
                ->with(['article', 'participants'])
                ->featured()
                ->chronological()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get events with location
     *
     * @return Collection
     */
    public function getEventsWithLocation(): Collection
    {
        return Cache::remember(
            'seerah_events_with_location',
            self::CACHE_TTL,
            fn() => $this->model
                ->withLocation()
                ->chronological()
                ->get(['id', 'slug', 'title', 'location', 'latitude', 'longitude', 'event_type', 'hijri_year'])
        );
    }

    /**
     * Search events
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['article', 'participants'])
            ->where(function ($q) use ($query) {
                $q->whereRaw("JSON_SEARCH(title, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhereRaw("JSON_SEARCH(description, 'one', ?) IS NOT NULL", ["%{$query}%"])
                    ->orWhere('location', 'like', "%{$query}%");
            })
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events in chronological order
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with(['article', 'participants'])
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Find by slug with relations
     *
     * @param string $slug
     * @param array $columns
     * @param array $relations
     * @return Model|null
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $defaultRelations = ['article', 'participants'];
        $relations = empty($relations) ? $defaultRelations : $relations;

        return Cache::remember(
            "seerah_event_slug_{$slug}",
            self::CACHE_TTL,
            fn() => $this->model
                ->with($relations)
                ->where('slug', $slug)
                ->first($columns)
        );
    }

    /**
     * Clear cache for event
     *
     * @param int $id
     * @return void
     */
    public function clearCacheForModel(int $id): void
    {
        $event = $this->find($id);
        if ($event) {
            Cache::forget("seerah_event_slug_{$event->slug}");
            Cache::forget('seerah_featured_10');
            Cache::forget('seerah_events_with_location');
        }
    }
}

