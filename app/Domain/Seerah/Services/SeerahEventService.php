<?php

declare(strict_types=1);

namespace App\Domain\Seerah\Services;

use App\Domain\Seerah\DTOs\SeerahEventData;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Repositories\SeerahEventRepositoryInterface;
use App\Domain\Shared\Contracts\MediaLibraryLookup;
use App\Domain\Shared\Contracts\MediaReference;
use App\Domain\Shared\Services\BaseService;
use App\Domain\Shared\Services\PublicImageService;
use App\Exceptions\Domain\ResourceNotFoundException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * @extends BaseService<SeerahEventRepositoryInterface>
 */
class SeerahEventService extends BaseService
{
    private const THUMB_DIRECTORY = 'seerah-events/thumbs';

    private const COVER_DIRECTORY = 'seerah-events/covers';

    private const GALLERY_DIRECTORY = 'seerah-events/gallery';

    /**
     * Columns that are NOT NULL in `seerah_events`. An empty submission must
     * leave them alone rather than null them out.
     *
     * @var array<int, string>
     */
    private const NON_NULLABLE_COLUMNS = ['slug', 'event_type', 'status', 'order'];

    public function __construct(
        SeerahEventRepositoryInterface $repository,
        private readonly PublicImageService $images,
        private readonly MediaLibraryLookup $mediaLibrary,
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
     * Get a published event by slug, with its attached media resolved.
     *
     * @throws ResourceNotFoundException
     */
    public function getEventBySlug(string $slug): SeerahEvent
    {
        /** @var SeerahEvent|null $event */
        $event = $this->repository->findBySlug($slug);

        if (! $event) {
            throw new ResourceNotFoundException("Timeline event not found: {$slug}");
        }

        $event->setRelation('resolvedMedia', collect($this->resolveMedia($event)));

        return $event;
    }

    /**
     * @return array{event: SeerahEvent, prev: ?SeerahEvent, next: ?SeerahEvent, related: Collection<int, SeerahEvent>, media: array<int, MediaReference>}
     *
     * @throws ResourceNotFoundException
     */
    public function getPublicEventPage(string $slug, string $locale): array
    {
        $page = $this->repository->getPublicPage($slug, $locale);

        if ($page === null) {
            throw new ResourceNotFoundException("Timeline event not found: {$slug}");
        }

        $page['media'] = $this->resolveMedia($page['event']);

        return $page;
    }

    /**
     * Edit-form state: the event with everything the form round-trips, plus the
     * attached media resolved for the picker.
     *
     * @return array{event: SeerahEvent, media: array<int, MediaReference>}
     */
    public function getEditPayload(int $id): array
    {
        /** @var SeerahEvent $event */
        $event = $this->repository->findOrFail($id);
        $event->loadMissing(['translations', 'gallery', 'mediaLinks', 'place']);

        return ['event' => $event, 'media' => $this->resolveMedia($event)];
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
     * Create an event with its images, translations, gallery and media.
     *
     * Images are stored before the transaction (file I/O cannot roll back) and
     * removed again if the write fails, so a failure leaves no orphans behind.
     */
    public function createEvent(SeerahEventData $data): SeerahEvent
    {
        $thumbPath = $this->images->store($data->thumb, self::THUMB_DIRECTORY, 'seerah-events');
        $coverPath = $this->images->store($data->cover, self::COVER_DIRECTORY, 'seerah-events');

        try {
            $event = DB::transaction(function () use ($data, $thumbPath, $coverPath): SeerahEvent {
                /** @var SeerahEvent $event */
                $event = $this->repository->create([
                    'slug' => $data->slug,
                    'event_type' => $data->eventType,
                    'sm_id' => $data->smId,
                    'order' => $data->order ?? 0,
                    'thumb_url' => $thumbPath,
                    'cover_url' => $coverPath,
                    'hijri_year' => $data->hijriYear,
                    'hijri_month' => $data->hijriMonth,
                    'hijri_day' => $data->hijriDay,
                    'gregorian_date' => $data->gregorianDate,
                    'location_id' => $data->locationId,
                    'status' => $data->status ?? 'draft',
                    'era' => $data->era,
                    'statistics' => $data->statistics !== [] ? $data->statistics : null,
                ]);

                $this->syncContent($event, $data);

                return $event;
            });
        } catch (\Throwable $e) {
            $this->images->delete($thumbPath);
            $this->images->delete($coverPath);

            throw $e;
        }

        // Bump the cache once, after the whole write has committed.
        $this->repository->clearCache();

        return $event;
    }

    /**
     * Update an event. Only submitted keys are written (see SeerahEventData).
     *
     * @throws ResourceNotFoundException
     */
    public function updateEvent(int $id, SeerahEventData $data): SeerahEvent
    {
        /** @var SeerahEvent|null $current */
        $current = $this->repository->find($id);

        if ($current === null) {
            throw new ResourceNotFoundException("Timeline event not found: {$id}");
        }

        $payload = $this->buildUpdatePayload($data);
        $superseded = [];

        foreach ([
            'thumb_url' => [$data->thumb, $data->removeThumb, self::THUMB_DIRECTORY],
            'cover_url' => [$data->cover, $data->removeCover, self::COVER_DIRECTORY],
        ] as $column => [$source, $remove, $directory]) {
            $stored = $source instanceof UploadedFile || is_int($source)
                ? $this->images->store($source, $directory, 'seerah-events')
                : null;

            if ($stored !== null || $remove) {
                $payload[$column] = $stored;
                $superseded[] = $current->{$column};
            }
        }

        $event = DB::transaction(function () use ($id, $data, $payload): SeerahEvent {
            $this->repository->update($id, $payload);

            /** @var SeerahEvent $event */
            $event = $this->repository->find($id);
            $this->syncContent($event, $data);

            return $event;
        });

        // Drop superseded files only once the new paths are committed.
        array_map($this->images->delete(...), $superseded);

        $this->repository->clearCache();

        return $event;
    }

    /**
     * Delete an event and every file it owns.
     *
     * @throws ResourceNotFoundException
     */
    public function deleteEvent(int $id): bool
    {
        /** @var SeerahEvent|null $event */
        $event = $this->repository->find($id);

        if ($event === null) {
            throw new ResourceNotFoundException("Timeline event not found: {$id}");
        }

        $files = [$event->thumb_url, $event->cover_url, ...$event->gallery()->pluck('image')->all()];

        // Translations, gallery and media rows go with it (FK cascade).
        $this->repository->delete($id);

        array_map($this->images->delete(...), $files);

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
     * Public events directory — search + type + milestone + era + dispute + sort.
     *
     * @param  array<string, mixed>  $filters
     */
    public function paginatePublicFiltered(array $filters, string $locale, int $perPage = 12): LengthAwarePaginator
    {
        return $this->repository->paginatePublicFiltered($filters, $locale, $perPage);
    }

    /**
     * @return array{disputed: int}
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
     * @return array{total: int, categories: array<string, int>, published: int, draft: int}
     */
    public function getIndexStats(): array
    {
        return $this->repository->getIndexStats();
    }

    /**
     * Translations, per-locale arrays, gallery and media — everything after the
     * event row itself. Runs inside the caller's transaction.
     */
    private function syncContent(SeerahEvent $event, SeerahEventData $data): void
    {
        $event->syncTranslations([
            'title' => $data->title,
            'description' => $data->description,
            'content' => $data->content,
            'dispute_note' => $data->disputeNote,
        ]);

        if ($data->hasInput('metadata')) {
            $this->syncLocaleArray($event, 'metadata', $data->metadata);
        }

        if ($data->hasInput('sources')) {
            $this->syncLocaleArray($event, 'sources', $data->sources);
        }

        if ($data->hasInput('gallery')) {
            $this->syncGallery($event, $data->gallery, $data->galleryCaptions);
        }

        if ($data->hasInput('media_items')) {
            $this->syncMedia($event, $data->mediaItems);
        }
    }

    /**
     * Persist a JSON-array translation field for each submitted locale.
     *
     * These are arrays, not strings, so they cannot ride through
     * syncTranslations() — HasLocalizedContent only handles scalar fields.
     * Locales that were not submitted are left untouched.
     *
     * @param  array<string, array<int, array<string, string>>>  $byLocale
     */
    private function syncLocaleArray(SeerahEvent $event, string $field, array $byLocale): void
    {
        foreach ($byLocale as $locale => $rows) {
            $translation = $event->translations()->firstOrNew(['locale' => $locale]);

            if (! $translation->exists && $rows === []) {
                continue;
            }

            $translation->forceFill([$field => $rows !== [] ? array_values($rows) : null])->save();
        }
    }

    /**
     * Replace-on-submit, keyed by media-library id — the same semantics as the
     * article gallery. Kept items are reordered and re-captioned in place (their
     * public copy is not re-made); removed items lose their row and their file.
     *
     * @param  array<int, int>  $mediaIds
     * @param  array<int|string, array<string, string|null>>  $captions
     */
    private function syncGallery(SeerahEvent $event, array $mediaIds, array $captions): void
    {
        $existing = $event->gallery()->get()->keyBy('media_id');

        foreach ($existing as $mediaId => $item) {
            if (! in_array((int) $mediaId, $mediaIds, true)) {
                $this->images->delete($item->image);
                $item->delete();
            }
        }

        foreach ($mediaIds as $order => $mediaId) {
            $caption = array_filter((array) ($captions[$mediaId] ?? []), fn ($text): bool => filled($text));
            $attributes = ['order' => $order, 'caption' => $caption !== [] ? $caption : null];

            if ($existing->has($mediaId)) {
                $existing->get($mediaId)->update($attributes);

                continue;
            }

            $path = $this->images->store($mediaId, self::GALLERY_DIRECTORY, 'seerah-events');

            if ($path !== null) {
                $event->gallery()->create(['media_id' => $mediaId, 'image' => $path] + $attributes);
            }
        }
    }

    /**
     * @param  array<int, int>  $mediaIds
     */
    private function syncMedia(SeerahEvent $event, array $mediaIds): void
    {
        $event->mediaLinks()->whereNotIn('media_id', $mediaIds)->delete();

        $current = $event->mediaLinks()->pluck('media_id')->map(fn ($id): int => (int) $id)->all();

        foreach (array_diff($mediaIds, $current) as $mediaId) {
            $event->mediaLinks()->create(['media_id' => $mediaId]);
        }
    }

    /**
     * @return array<int, MediaReference> in attachment order
     */
    private function resolveMedia(SeerahEvent $event): array
    {
        $ids = $event->mediaLinks->pluck('media_id')->map(fn ($id): int => (int) $id)->all();

        return array_values($this->mediaLibrary->byIds($ids));
    }

    /**
     * Build the update payload from the keys that were actually submitted.
     *
     * A submitted key is written even when its value is null, so nullable
     * fields can be cleared; an omitted key is untouched.
     *
     * @return array<string, mixed>
     */
    private function buildUpdatePayload(SeerahEventData $data): array
    {
        $candidates = [
            'slug' => $data->slug,
            'event_type' => $data->eventType,
            'sm_id' => $data->smId,
            'order' => $data->order,
            'hijri_year' => $data->hijriYear,
            'hijri_month' => $data->hijriMonth,
            'hijri_day' => $data->hijriDay,
            'gregorian_date' => $data->gregorianDate,
            'location_id' => $data->locationId,
            'status' => $data->status,
            'era' => $data->era,
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

        if ($data->hasInput('statistics')) {
            $payload['statistics'] = $data->statistics !== [] ? $data->statistics : null;
        }

        return $payload;
    }
}
