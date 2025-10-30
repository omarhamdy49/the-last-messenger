<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

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
    public function createEvent(array $data): SeerahEvent
    {
        $data['views_count'] = 0;
        $data['display_order'] = $data['display_order'] ?? 0;
        $data['is_featured'] = $data['is_featured'] ?? false;

        return $this->repository->create($data);
    }

    /**
     * Update an event
     *
     * @throws ResourceNotFoundException
     */
    public function updateEvent(int $id, array $data): SeerahEvent
    {
        $updated = $this->repository->update($id, $data);

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
}
