<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\DTOs\CreateSeerahEventData;
use App\Domain\Seerah\DTOs\UpdateSeerahEventData;
use App\Domain\Seerah\Enums\EventType;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SeerahEventService extends BaseService
{
    /**
     * SeerahEventService constructor
     */
    public function __construct(
        SeerahEventRepositoryInterface $repository
    ) {
        $this->repository = $repository;
    }

    /**
     * Get all events chronologically
     */
    public function getAllEvents(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getChronological($perPage);
    }

    /**
     * Get event by slug
     *
     * @throws ResourceNotFoundException
     */
    public function getEventBySlug(string $slug): SeerahEvent
    {
        $event = $this->repository->findBySlug($slug);

        if (! $event) {
            throw new ResourceNotFoundException("Timeline event not found: {$slug}");
        }

        // Increment views
        $event->incrementViews();

        return $event;
    }

    /**
     * Get events by type
     */
    public function getEventsByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByType($type, $perPage);
    }

    /**
     * Get events by Hijri year
     */
    public function getEventsByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByHijriYear($year, $perPage);
    }

    /**
     * Get events by Gregorian year
     */
    public function getEventsByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByGregorianYear($year, $perPage);
    }

    /**
     * Get featured events
     */
    public function getFeaturedEvents(int $limit = 10): Collection
    {
        return $this->repository->getFeatured($limit);
    }

    /**
     * Get events with location (for map)
     */
    public function getEventsWithLocation(): Collection
    {
        return $this->repository->getEventsWithLocation();
    }

    /**
     * Search events
     */
    public function searchEvents(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Create a new event
     */
    public function createEvent(CreateSeerahEventData $data): SeerahEvent
    {
        return $this->repository->create([
            'slug' => $data->slug,
            'hijri_date' => $data->hijriDate,
            'hijri_year' => $data->hijriYear,
            'hijri_month' => $data->hijriMonth,
            'hijri_day' => $data->hijriDay,
            'gregorian_date' => $data->gregorianDate,
            'gregorian_year' => $data->gregorianYear,
            'event_type' => $data->eventType,
            'importance' => $data->importance,
            'location' => $data->location,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'related_article_id' => $data->relatedArticleId,
            'featured_image' => $data->featuredImage,
            'display_order' => $data->displayOrder,
            'is_featured' => $data->isFeatured,
            'views_count' => 0,
        ]);
    }

    /**
     * Update an event
     *
     * @throws ResourceNotFoundException
     */
    public function updateEvent(int $id, UpdateSeerahEventData $data): SeerahEvent
    {
        $payload = array_filter([
            'slug' => $data->slug,
            'hijri_date' => $data->hijriDate,
            'hijri_year' => $data->hijriYear,
            'hijri_month' => $data->hijriMonth,
            'hijri_day' => $data->hijriDay,
            'gregorian_date' => $data->gregorianDate,
            'gregorian_year' => $data->gregorianYear,
            'event_type' => $data->eventType,
            'importance' => $data->importance,
            'location' => $data->location,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'related_article_id' => $data->relatedArticleId,
            'featured_image' => $data->featuredImage,
            'display_order' => $data->displayOrder,
            'is_featured' => $data->isFeatured,
        ], fn ($v) => $v !== null);

        $updated = $this->repository->update($id, $payload);

        if (! $updated) {
            throw new ResourceNotFoundException("Timeline event not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return $this->repository->find($id);
    }

    /**
     * Delete an event
     *
     * @throws ResourceNotFoundException
     */
    public function deleteEvent(int $id): bool
    {
        $deleted = $this->repository->delete($id);

        if (! $deleted) {
            throw new ResourceNotFoundException("Timeline event not found: {$id}");
        }

        $this->repository->clearCacheForModel($id);

        return true;
    }

    /**
     * Count events by category (event type)
     *
     * @return array<string, int>
     */
    public function countByCategory(): array
    {
        $eventTypes = EventType::cases();
        $counts = [];

        foreach ($eventTypes as $type) {
            $counts[$type->value] = $this->repository->count([
                'event_type' => $type->value,
            ]);
        }

        return $counts;
    }

    /**
     * Search events (alias for searchEvents)
     */
    public function search(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->searchEvents($query, $perPage);
    }

    /**
     * Get aggregate stats for the index page (single query, no N+1).
     *
     * @return array{total: int, categories: array<string, int>}
     */
    public function getIndexStats(): array
    {
        return $this->repository->getIndexStats();
    }
}
