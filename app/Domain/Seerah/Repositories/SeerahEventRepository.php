<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Repositories;

use App\Domain\Seerah\Enums\EventType;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Repositories\BaseRepository;
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
        return new SeerahEvent;
    }

    /**
     * Get events by type
     */
    public function getByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->defaultRelations())
            ->byType($type)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Hijri year
     */
    public function getByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->defaultRelations())
            ->byHijriYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get events by Gregorian year
     */
    public function getByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->defaultRelations())
            ->byGregorianYear($year)
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Get featured events
     */
    public function getFeatured(int $limit = 10): Collection
    {
        return Cache::remember(
            "seerah_featured_{$limit}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($this->defaultRelations())
                ->featured()
                ->chronological()
                ->limit($limit)
                ->get()
        );
    }

    /**
     * Get events with location
     */
    public function getEventsWithLocation(): Collection
    {
        return Cache::remember(
            'seerah_events_with_location',
            self::CACHE_TTL,
            fn () => $this->model
                ->withLocation()
                ->chronological()
                ->get(['id', 'slug', 'title', 'location', 'latitude', 'longitude', 'event_type', 'hijri_year'])
        );
    }

    /**
     * Search events
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->defaultRelations())
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
     */
    public function getChronological(int $perPage = 15): LengthAwarePaginator
    {
        return $this->model
            ->with($this->defaultRelations())
            ->chronological()
            ->paginate($perPage);
    }

    /**
     * Find by slug with relations
     */
    public function findBySlug(string $slug, array $columns = ['*'], array $relations = []): ?Model
    {
        $relations = empty($relations) ? $this->defaultRelations() : $relations;

        return Cache::remember(
            "seerah_event_slug_{$slug}",
            self::CACHE_TTL,
            fn () => $this->model
                ->with($relations)
                ->where('slug', $slug)
                ->first($columns)
        );
    }

    /**
     * Relations required for event detail responses.
     *
     * @return array<int, string>
     */
    private function defaultRelations(): array
    {
        return [
            'article.locales',
            'article.category',
            'article.categories',
            'article.tags',
            'article.mediaItems',
            'participants',
        ];
    }

    /**
     * Clear cache for event
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
