<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\DTOs\CreateSeerahEventData;
use App\Domain\Seerah\DTOs\UpdateSeerahEventData;
use App\Domain\Seerah\Enums\EventImportance;
use App\Domain\Seerah\Enums\ParticipantRole;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Models\SeerahEventTimelineItem;
use App\Domain\Seerah\Models\SeerahParticipant;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Services\BaseService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseService<SeerahEventRepositoryInterface>
 */
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
    public function createEvent(CreateSeerahEventData $data, ?string $featuredImagePath = null, array $galleryPaths = []): SeerahEvent
    {
        $event = DB::transaction(function () use ($data, $featuredImagePath, $galleryPaths): SeerahEvent {
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
                'era' => $data->era,
                'phase' => $data->phase,
                'is_pivotal' => $data->isPivotal,
                'statistics' => $data->statistics !== [] ? $data->statistics : null,
                'gallery' => $galleryPaths !== [] ? $galleryPaths : null,
            ]);

            $event->syncTranslations([
                'title' => $data->title,
                'description' => $data->description,
                'content' => $data->content,
                'dispute_note' => $data->disputeNote,
            ]);

            $this->syncLocaleArrays($event, $data->lessons, $data->sources);

            $this->syncTimelineItems($event, $data->timelineItems);
            $this->syncParticipants($event, $data->participants);

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
    public function updateEvent(int $id, UpdateSeerahEventData $data, ?string $featuredImagePath = null, array $galleryPaths = []): SeerahEvent
    {
        $event = DB::transaction(function () use ($id, $data, $featuredImagePath, $galleryPaths): SeerahEvent {
            $payload = $this->buildUpdatePayload($data, $featuredImagePath, $galleryPaths);

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
                'dispute_note' => $data->disputeNote,
            ]);

            $this->syncLocaleArrays($event, $data->lessons, $data->sources);

            // Both repeaters are all-or-nothing: the form always posts the full
            // set, so an absent key means "the form had no repeater" (partial
            // API update) and must not wipe existing rows.
            if ($data->hasInput('timeline_items')) {
                $this->syncTimelineItems($event, $data->timelineItems);
            }
            if ($data->hasInput('participants')) {
                $this->syncParticipants($event, $data->participants);
            }

            return $event;
        });

        // Bump the cache once, after the whole write has committed.
        $this->repository->clearCache();

        return $event;
    }

    /**
     * Replace the event's inner timeline with the submitted rows.
     *
     * Rebuilt wholesale rather than diffed: the rows carry no stable client-side
     * identity, and a 4–6 row collection makes diffing pure overhead.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncTimelineItems(SeerahEvent $event, array $rows): void
    {
        $event->timelineItems()->delete();

        foreach (array_values($rows) as $index => $row) {
            $item = SeerahEventTimelineItem::create([
                'seerah_event_id' => $event->id,
                'display_order' => $index + 1,
            ]);

            $item->syncTranslations([
                'title' => (array) ($row['title'] ?? []),
                'description' => (array) ($row['description'] ?? []),
            ]);
        }
    }

    /**
     * Replace the event's participants with the submitted rows.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncParticipants(SeerahEvent $event, array $rows): void
    {
        $event->participants()->delete();

        foreach ($rows as $row) {
            $participant = SeerahParticipant::create([
                'event_id' => $event->id,
                'participant_type' => 'person',
                'participant_id' => isset($row['participant_id']) && $row['participant_id'] !== ''
                    ? (int) $row['participant_id']
                    : null,
                'role' => $row['role'] ?? ParticipantRole::OTHER->value,
            ]);

            $participant->syncTranslations([
                'name' => (array) ($row['name'] ?? []),
                'description' => (array) ($row['description'] ?? []),
            ]);
        }
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
    private function buildUpdatePayload(UpdateSeerahEventData $data, ?string $featuredImagePath, array $galleryPaths = []): array
    {
        $candidates = [
            'era' => $data->era,
            'phase' => $data->phase,
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

        // Checkbox semantics: an absent `is_featured` / `is_pivotal` means
        // "unchecked", so both are always written rather than keyed off presence.
        $payload['is_featured'] = $data->isFeatured;
        $payload['is_pivotal'] = $data->isPivotal;

        if ($data->hasInput('statistics')) {
            $payload['statistics'] = $data->statistics !== [] ? $data->statistics : null;
        }

        // The form re-posts the surviving old paths in `existing_gallery`, so
        // removing an image is a submit without it. Only touch the column when
        // the form actually carried gallery state.
        if ($data->hasInput('existing_gallery') || $galleryPaths !== []) {
            $merged = array_values(array_unique([...$data->existingGallery, ...$galleryPaths]));
            $payload['gallery'] = $merged !== [] ? $merged : null;
        }

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
     * Public filter-pill counts — published + translation-ready only.
     *
     * @return array<string, int>
     */
    public function countPublicByCategory(string $locale): array
    {
        return $this->repository->countPublicByCategory($locale);
    }

    /**
     * Public events directory — search + type + milestone + importance +
     * pivotal + dispute + sort, all composable.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginatePublicFiltered(array $filters, string $locale, int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginatePublicFiltered($filters, $locale, $perPage);
    }

    /**
     * @return array{pivotal: int, disputed: int, importance: array<string, int>}
     */
    public function publicFilterFacets(string $locale): array
    {
        return $this->repository->publicFilterFacets($locale);
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

    /**
     * Persist lessons + sources onto the Arabic locale row.
     *
     * These are JSON arrays, not strings, so they cannot ride through
     * syncTranslations() — HasLocalizedContent only handles scalar fields and
     * getTranslation() is typed ?string.
     *
     * @param  array<int, array<string, string>>  $lessons
     * @param  array<int, array<string, string>>  $sources
     */
    private function syncLocaleArrays(SeerahEvent $event, array $lessons, array $sources): void
    {
        $translation = $event->translations()->firstOrCreate(
            ['locale' => 'ar'],
            ['is_ready' => true],
        );

        $translation->forceFill([
            'lessons' => $lessons !== [] ? array_values($lessons) : null,
            'sources' => $sources !== [] ? array_values($sources) : null,
        ])->save();
    }
}
