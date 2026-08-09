<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\DTOs\CreateSeerahEventData;
use App\Domain\Seerah\DTOs\UpdateSeerahEventData;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SeerahEventService extends BaseService
{
    /**
     * Columns that are NOT NULL in `seerah_events` and carry a DB default.
     * An empty submission must leave them alone rather than null them out.
     *
     * @var array<int, string>
     */
    private const NON_NULLABLE_COLUMNS = ['slug', 'importance', 'status', 'display_order'];

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
     * @return array{event: SeerahEvent, prev: ?SeerahEvent, next: ?SeerahEvent, related: Collection<int, SeerahEvent>}
     *
     * @throws ResourceNotFoundException
     */
    public function getPublicEventPage(string $slug, string $locale): array
    {
        $page = $this->repository->getPublicPage($slug, $locale);

        if ($page === null) {
            throw new ResourceNotFoundException("Timeline event not found: {$slug}");
        }

        $page['event']->incrementViews();

        return $page;
    }

    /**
     * Get events by type (type id or slug)
     */
    public function getEventsByType(int|string $type, int $perPage = 15): LengthAwarePaginator
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
     * Create a new event together with its translations, in one transaction.
     *
     * The event row and its translations are written atomically so a failure
     * can never leave a title-less event behind.
     *
     * @param  string|null  $featuredImagePath  Path of the already-stored upload, if any
     */
    public function createEvent(CreateSeerahEventData $data, ?string $featuredImagePath = null): SeerahEvent
    {
        $event = DB::transaction(function () use ($data, $featuredImagePath): SeerahEvent {
            /** @var SeerahEvent $event */
            $event = $this->repository->create([
                'slug' => $data->slug,
                'hijri_date' => $data->hijriDate,
                'hijri_year' => $data->hijriYear,
                'hijri_month' => $data->hijriMonth,
                'hijri_day' => $data->hijriDay,
                'gregorian_date' => $data->gregorianDate,
                'gregorian_year' => $data->gregorianYear,
                'seerah_event_type_id' => $data->seerahEventTypeId,
                'seerah_milestone_id' => $data->seerahMilestoneId,
                'importance' => $data->importance ?? EventImportance::MEDIUM->value,
                'status' => $data->status ?? SeerahEventStatus::PUBLISHED->value,
                'location' => $data->location,
                'latitude' => $data->latitude,
                'longitude' => $data->longitude,
                'related_article_id' => $data->relatedArticleId,
                'featured_image' => $featuredImagePath,
                'display_order' => $data->displayOrder,
                'is_featured' => $data->isFeatured,
                'views_count' => 0,
            ]);

            $event->syncTranslations([
                'title' => $data->title,
                'description' => $data->description,
                'content' => $data->content,
            ]);

            return $event;
        });

        // Bump the cache once, after the whole write has committed.
        $this->repository->clearCache();

        return $event;
    }

    /**
     * Update an event together with its translations, in one transaction.
     *
     * @param  string|null  $featuredImagePath  Path of the already-stored upload, if any
     *
     * @throws ResourceNotFoundException
     */
    public function updateEvent(int $id, UpdateSeerahEventData $data, ?string $featuredImagePath = null): SeerahEvent
    {
        $event = DB::transaction(function () use ($id, $data, $featuredImagePath): SeerahEvent {
            $payload = $this->buildUpdatePayload($data, $featuredImagePath);

            if (! $this->repository->update($id, $payload)) {
                throw new ResourceNotFoundException("Timeline event not found: {$id}");
            }

            /** @var SeerahEvent|null $event */
            $event = $this->repository->find($id);

            if ($event === null) {
                throw new ResourceNotFoundException("Timeline event not found: {$id}");
            }

            $event->syncTranslations([
                'title' => $data->title,
                'description' => $data->description,
                'content' => $data->content,
            ]);

            return $event;
        });

        // Bump the cache once, after the whole write has committed.
        $this->repository->clearCache();

        return $event;
    }

    /**
     * Build the update payload from the keys that were actually submitted.
     *
     * Null-stripping would make nullable fields (location, coordinates, hijri
     * parts, milestone, related article) impossible to clear, so a submitted
     * key is written even when its value is null; an omitted key is untouched.
     *
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(UpdateSeerahEventData $data, ?string $featuredImagePath): array
    {
        $candidates = [
            'slug' => $data->slug,
            'hijri_date' => $data->hijriDate,
            'hijri_year' => $data->hijriYear,
            'hijri_month' => $data->hijriMonth,
            'hijri_day' => $data->hijriDay,
            'gregorian_date' => $data->gregorianDate,
            'gregorian_year' => $data->gregorianYear,
            'seerah_event_type_id' => $data->seerahEventTypeId,
            'seerah_milestone_id' => $data->seerahMilestoneId,
            'importance' => $data->importance,
            'status' => $data->status,
            'location' => $data->location,
            'latitude' => $data->latitude,
            'longitude' => $data->longitude,
            'related_article_id' => $data->relatedArticleId,
            'display_order' => $data->displayOrder,
        ];

        $payload = [];

        foreach ($candidates as $column => $value) {
            if (! $data->hasInput($column)) {
                continue;
            }

            if ($value === null && in_array($column, self::NON_NULLABLE_COLUMNS, true)) {
                continue;
            }

            $payload[$column] = $value;
        }

        // Checkbox semantics: an absent `is_featured` means "unchecked",
        // so it is always written rather than keyed off presence.
        $payload['is_featured'] = $data->isFeatured;

        if ($featuredImagePath !== null) {
            $payload['featured_image'] = $featuredImagePath;
        }

        return $payload;
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

        return true;
    }

    /**
     * Count events by category (event type slug), single aggregate query.
     *
     * @return array<string, int>
     */
    public function countByCategory(): array
    {
        return $this->repository->getIndexStats()['categories'];
    }

    /**
     * Paginate events for the admin index with optional whitelisted filters.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginateWithFilters(array $filters, int $perPage = 15): LengthAwarePaginator
    {
        return $this->repository->paginateWithFilters($filters, $perPage);
    }

    /**
     * Bulk-update the publication status of the given events.
     *
     * @param  array<int>  $ids
     */
    public function bulkUpdateStatus(array $ids, string $status): int
    {
        return $this->repository->bulkUpdateStatus($ids, $status);
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
