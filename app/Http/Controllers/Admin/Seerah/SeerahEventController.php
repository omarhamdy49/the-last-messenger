<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seerah;

use App\Domain\Seerah\DTOs\SeerahEventData;
use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Domain\Shared\Contracts\ContentReference;
use App\Domain\Shared\Contracts\MediaReference;
use App\Domain\Shared\Enums\LinkedContentType;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventRequest;
use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Admin Seerah Event Controller
 * Handles Seerah event management for admin portal
 */
class SeerahEventController extends Controller
{
    public function __construct(
        protected SeerahEventService $seerahEventService,
        protected SeerahEventTypeService $eventTypeService,
        protected SeerahMilestoneService $milestoneService,
        protected AdminLoggerService $logger,
    ) {}

    /**
     * Display a listing of all Seerah events
     *
     * GET /su/portal/v1/admin/seerah-events
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        $filters = array_filter([
            'type' => $request->string('event_type')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'era' => EventEra::tryFrom($request->string('era')->toString())?->value,
            'milestone' => $request->integer('milestone') ?: null,
            'search' => $request->string('search')->toString() ?: null,
            'location_id' => $request->integer('location_id') ?: null,
        ]);

        $events = $this->seerahEventService->paginateWithFilters($filters, $perPage);

        // Single aggregate query delegated to repository via service
        $stats = $this->seerahEventService->getIndexStats();
        $eventTypes = $this->eventTypeService->getForDropdown();
        $milestones = $this->milestoneService->getForDropdown();
        $statuses = SeerahEventStatus::cases();
        $eras = EventEra::cases();

        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $events->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $events->currentPage(),
                    'per_page' => $events->perPage(),
                    'total' => $events->total(),
                    'last_page' => $events->lastPage(),
                ],
            ]);
        }

        return view('admin.seerah-events.index', compact('events', 'stats', 'eventTypes', 'milestones', 'statuses', 'eras'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-events/create
     */
    public function create(): View
    {
        return view('admin.seerah-events.create', $this->formOptions());
    }

    /**
     * POST /su/portal/v1/admin/seerah-events
     */
    public function store(StoreSeerahEventRequest $request): RedirectResponse|JsonResponse
    {
        $event = $this->seerahEventService->createEvent(
            SeerahEventData::fromArray($request->validated(), $request->file('thumb'), $request->file('cover'))
        );

        $this->logger->logCreate($event, $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => ['id' => $event->id, 'slug' => $event->slug]], 201);
        }

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم إنشاء الحدث بنجاح');
    }

    /**
     * GET /su/portal/v1/admin/seerah-events/{id}
     */
    public function show(int $id): View
    {
        ['event' => $event, 'media' => $media, 'links' => $links] = $this->seerahEventService->getEditPayload($id);
        $event->loadMissing(['type:id,slug,metadata,locale', 'milestone.translations']);

        return view('admin.seerah-events.show', compact('event', 'media', 'links'));
    }

    /**
     * GET /su/portal/v1/admin/seerah-events/{id}/edit
     */
    public function edit(int $id): View
    {
        ['event' => $event, 'media' => $media, 'links' => $links] = $this->seerahEventService->getEditPayload($id);
        $shape = fn (ContentReference $ref): array => [
            'id' => $ref->id,
            'title' => $ref->title,
            'subtitle' => $ref->subtitle,
            'thumb' => $ref->thumbUrl,
        ];

        return view('admin.seerah-events.edit', [
            ...$this->formOptions(),
            'event' => $event,
            // Shaped like the article wizard's payload: `id` is the media-library
            // id the picker works in, which is what the form posts back.
            'galleryItems' => $event->gallery->map(fn ($item): array => [
                'id' => $item->media_id,
                'url' => Storage::disk('public')->url($item->image),
                'caption' => $item->caption ?? (object) [],
            ])->filter(fn (array $item): bool => $item['id'] !== null)->values()->all(),
            'mediaItems' => array_map(fn (MediaReference $media): array => [
                'id' => $media->id,
                'title' => $media->title,
                'type' => $media->type,
                'thumbnail_url' => $media->thumbnailUrl,
            ], $media),
            'episodeItems' => array_map($shape, $links[LinkedContentType::EPISODE->value] ?? []),
            'linkItems' => array_map(fn (array $refs): array => array_map($shape, $refs), $links),
        ]);
    }

    /**
     * PUT/PATCH /su/portal/v1/admin/seerah-events/{id}
     */
    public function update(UpdateSeerahEventRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $oldEvent = $this->seerahEventService->findOrFail($id);
        $oldValues = $oldEvent->toArray();

        $event = $this->seerahEventService->updateEvent(
            $id,
            SeerahEventData::fromArray($request->validated(), $request->file('thumb'), $request->file('cover'))
        );

        $this->logger->logUpdate($event, $oldValues, $event->toArray(), $request->user());

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => ['id' => $event->id, 'slug' => $event->slug]]);
        }

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم تحديث الحدث بنجاح');
    }

    /**
     * DELETE /su/portal/v1/admin/seerah-events/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $event = $this->seerahEventService->findOrFail($id);

        $this->seerahEventService->deleteEvent($id);

        $this->logger->logDelete($event, request()->user());

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم حذف الحدث بنجاح');
    }

    /**
     * POST /su/portal/v1/admin/seerah-events/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seerah_events,id'],
        ]);

        $ids = array_map('intval', $request->input('ids'));
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->seerahEventService->deleteEvent($id);
                $deleted++;
            } catch (\Throwable $e) {
                report($e);
                // Continue deleting
            }
        }

        $this->logger->logBulkAction('bulk_delete', SeerahEvent::class, $ids, $request->user());

        return redirect()->route('admin.seerah-events.index')
            ->with('success', "تم حذف {$deleted} حدث بنجاح");
    }

    /**
     * POST /su/portal/v1/admin/seerah-events/bulk-status
     */
    public function bulkUpdateStatus(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:seerah_events,id'],
            'status' => ['required', 'string', 'in:draft,published'],
        ]);

        $updated = $this->seerahEventService->bulkUpdateStatus($validated['ids'], $validated['status']);

        $this->logger->logBulkAction(
            'bulk_status_'.$validated['status'],
            SeerahEvent::class,
            $validated['ids'],
            $request->user()
        );

        return redirect()->route('admin.seerah-events.index')
            ->with('success', __('admin.bulk_status_updated', ['count' => $updated]));
    }

    /**
     * GET /su/portal/v1/admin/seerah-events/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $events = $this->seerahEventService->search($request->input('q'), $request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            // Shaped for the article embed picker: a locale map title and the
            // type's display name, not the raw event_type foreign key.
            'data' => collect($events->items())->map(fn (SeerahEvent $event): array => [
                'id' => $event->id,
                'slug' => $event->slug,
                'title' => $event->getTranslations('title'),
                'hijri_year' => $event->hijri_year,
                'event_type' => $event->type?->name(),
            ])->all(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'eventTypes' => $this->eventTypeService->getForDropdown(),
            'milestones' => $this->milestoneService->getForDropdown(),
            'statuses' => SeerahEventStatus::cases(),
            'eras' => EventEra::cases(),
            'linkOptions' => $this->seerahEventService->linkOptions(),
        ];
    }
}
