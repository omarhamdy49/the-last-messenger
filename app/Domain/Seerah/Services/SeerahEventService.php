<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Shared\Services\BaseService;
use App\Domain\Seerah\Enums\EventType;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class SeerahEventService
{
    /**
     * SeerahEventService constructor
     *
     * @param SeerahEventRepositoryInterface $repository
     */
    public function __construct(
        private readonly SeerahEventRepositoryInterface $repository
    ) {
    }

    /**
     * Get all events chronologically
     *
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getAllEvents(int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getChronological($perPage);
    }

    /**
     * Get event by slug
     *
     * @param string $slug
     * @return SeerahEvent
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
     *
     * @param EventType|string $type
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEventsByType(EventType|string $type, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByType($type, $perPage);
    }

    /**
     * Get events by Hijri year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEventsByHijriYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByHijriYear($year, $perPage);
    }

    /**
     * Get events by Gregorian year
     *
     * @param int $year
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function getEventsByGregorianYear(int $year, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->getByGregorianYear($year, $perPage);
    }

    /**
     * Get featured events
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedEvents(int $limit = 10): Collection
    {
        return $this->repository->getFeatured($limit);
    }

    /**
     * Get events with location (for map)
     *
     * @return Collection
     */
    public function getEventsWithLocation(): Collection
    {
        return $this->repository->getEventsWithLocation();
    }

    /**
     * Search events
     *
     * @param string $query
     * @param int $perPage
     * @return LengthAwarePaginator
     */
    public function searchEvents(string $query, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->search($query, $perPage);
    }

    /**
     * Create a new event
     *
     * @param array $data
     * @return SeerahEvent
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
     * @param int $id
     * @param array $data
     * @return SeerahEvent
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
     * @param int $id
     * @return bool
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
}

