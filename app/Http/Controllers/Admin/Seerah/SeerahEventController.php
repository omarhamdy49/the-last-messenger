<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seerah;

use App\Domain\Seerah\DTOs\CreateSeerahEventData;
use App\Domain\Seerah\DTOs\UpdateSeerahEventData;
use App\Domain\Seerah\Enums\EventEra;
use App\Domain\Seerah\Enums\EventPhase;
use App\Domain\Seerah\Enums\ParticipantRole;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Domain\Shared\Services\PublicImageService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventRequest;
use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use App\Repositories\ArticleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Admin Seerah Event Controller
 * Handles Seerah event management for admin portal
 */
class SeerahEventController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected SeerahEventService $seerahEventService,
        protected SeerahEventTypeService $eventTypeService,
        protected SeerahMilestoneService $milestoneService,
        protected AdminLoggerService $logger,
        protected ArticleRepository $articleRepository,
        protected PublicImageService $images,
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
            'importance' => $request->string('importance')->toString() ?: null,
            'status' => $request->string('status')->toString() ?: null,
            'milestone' => $request->integer('milestone') ?: null,
            'search' => $request->string('search')->toString() ?: null,
        ]);

        $events = $this->seerahEventService->paginateWithFilters($filters, $perPage);

        // Single aggregate query delegated to repository via service
        $stats = $this->seerahEventService->getIndexStats();
        $eventTypes = $this->eventTypeService->getForDropdown();
        $milestones = $this->milestoneService->getForDropdown();
        $statuses = SeerahEventStatus::cases();

        // Return JSON for AJAX requests
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

        return view('admin.seerah-events.index', compact('events', 'stats', 'eventTypes', 'milestones', 'statuses'));
    }

    /**
     * Show the form for creating a new Seerah event
     *
     * GET /su/portal/v1/admin/seerah-events/create
     */
    public function create(): View
    {
        $articles = $this->articleRepository->getForDropdown();
        $eventTypes = $this->eventTypeService->getForDropdown();
        $milestones = $this->milestoneService->getForDropdown();
        $statuses = SeerahEventStatus::cases();
        $eras = EventEra::cases();
        $phases = EventPhase::cases();
        $participantRoles = ParticipantRole::cases();

        return view('admin.seerah-events.create', compact(
            'articles', 'eventTypes', 'milestones', 'statuses', 'eras', 'phases', 'participantRoles'
        ));
    }

    /**
     * Store a newly created Seerah event
     *
     * POST /su/portal/v1/admin/seerah-events
     */
    public function store(StoreSeerahEventRequest $request): RedirectResponse
    {
        $dto = CreateSeerahEventData::fromArray($request->validated(), $this->uploadedFeaturedImage($request));

        // Upload or media-library pick, resolved to a disk path before the service runs
        $storedImagePath = $this->resolveFeaturedImagePath($request, $dto->featuredImage);

        // Row + image path + translations are written in a single transaction
        $event = $this->seerahEventService->createEvent($dto, $storedImagePath, $this->storeGallery($request));

        // Log the creation
        $this->logger->logCreate($event, $request->user());

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم إنشاء الحدث بنجاح');
    }

    /**
     * Display the specified Seerah event
     *
     * GET /su/portal/v1/admin/seerah-events/{id}
     */
    public function show(int $id): View
    {
        $event = $this->seerahEventService->find($id);

        if (! $event) {
            abort(404, 'Seerah event not found');
        }

        $event->loadMissing(['translations', 'type:id,slug,icon,color,gradient', 'type.translations:id,seerah_event_type_id,locale,name', 'article.locales:id,article_id,locale,title,slug,is_ready', 'article.mediaItems', 'participants']);

        return view('admin.seerah-events.show', compact('event'));
    }

    /**
     * Show the form for editing the specified Seerah event
     *
     * GET /su/portal/v1/admin/seerah-events/{id}/edit
     */
    public function edit(int $id): View
    {
        $event = $this->seerahEventService->find($id);

        if (! $event) {
            abort(404, 'Seerah event not found');
        }

        $event->loadMissing([
            'translations',
            'article.locales:id,article_id,locale,title,slug,is_ready',
            'timelineItems.translations',
            'participants.translations',
        ]);
        $articles = $this->articleRepository->getForDropdown();
        $eventTypes = $this->eventTypeService->getForDropdown();
        $milestones = $this->milestoneService->getForDropdown();
        $statuses = SeerahEventStatus::cases();
        $eras = EventEra::cases();
        $phases = EventPhase::cases();
        $participantRoles = ParticipantRole::cases();

        return view('admin.seerah-events.edit', compact(
            'event', 'articles', 'eventTypes', 'milestones', 'statuses', 'eras', 'phases', 'participantRoles'
        ));
    }

    /**
     * Update the specified Seerah event
     *
     * PUT/PATCH /su/portal/v1/admin/seerah-events/{id}
     */
    public function update(UpdateSeerahEventRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldEvent = $this->seerahEventService->find($id);

        if (! $oldEvent) {
            abort(404, 'Seerah event not found');
        }

        $oldValues = $oldEvent->toArray();
        $oldImagePath = $oldEvent->featured_image;

        $dto = UpdateSeerahEventData::fromArray($request->validated(), $this->uploadedFeaturedImage($request));

        // Upload or media-library pick, resolved to a disk path before the service runs
        $storedImagePath = $this->resolveFeaturedImagePath($request, $dto->featuredImage);

        // Row + image path + translations are written in a single transaction
        $event = $this->seerahEventService->updateEvent($id, $dto, $storedImagePath, $this->storeGallery($request));

        // Drop the superseded file only once the new path is committed
        if ($storedImagePath !== null) {
            $this->deleteFeaturedImage($oldImagePath);
        }

        // Log the update with diff
        $this->logger->logUpdate($event, $oldValues, $event->toArray(), $request->user());

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم تحديث الحدث بنجاح');
    }

    /**
     * Remove the specified Seerah event
     *
     * DELETE /su/portal/v1/admin/seerah-events/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $event = $this->seerahEventService->findOrFail($id);

        $this->deleteFeaturedImage($event->featured_image);

        // Perform deletion
        $this->seerahEventService->delete($id);

        // Log the deletion
        $this->logger->logDelete($event, request()->user());

        return redirect()->route('admin.seerah-events.index')
            ->with('success', 'تم حذف الحدث بنجاح');
    }

    /**
     * Bulk delete Seerah events
     *
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
                $event = $this->seerahEventService->findOrFail($id);
                $this->deleteFeaturedImage($event->featured_image);
                $this->seerahEventService->delete($id);
                $deleted++;
            } catch (\Throwable $e) {
                report($e);
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            SeerahEvent::class,
            $ids,
            $request->user()
        );

        return redirect()->route('admin.seerah-events.index')
            ->with('success', "تم حذف {$deleted} حدث بنجاح");
    }

    /**
     * Bulk-update the publication status of Seerah events.
     *
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
     * Search Seerah events
     *
     * GET /su/portal/v1/admin/seerah-events/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $events = $this->seerahEventService->search($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $events->items(),
            'meta' => [
                'current_page' => $events->currentPage(),
                'last_page' => $events->lastPage(),
                'per_page' => $events->perPage(),
                'total' => $events->total(),
            ],
        ]);
    }

    /**
     * Pull the uploaded featured image off the request, if one was sent.
     */
    private function uploadedFeaturedImage(Request $request): ?UploadedFile
    {
        return $request->hasFile('featured_image') ? $request->file('featured_image') : null;
    }

    /**
     * Persist newly uploaded gallery images and return their disk paths,
     * followed by the paths of any images picked from the media library.
     *
     * @return array<int, string>
     */
    private function storeGallery(Request $request): array
    {
        $paths = [];

        foreach ($request->file('gallery', []) as $file) {
            if ($file instanceof UploadedFile && ($stored = $this->images->store($file, 'seerah-events/gallery', 'seerah-events'))) {
                $paths[] = $stored;
            }
        }

        return [...$paths, ...$this->mediaLibraryPaths($request->input('gallery_ids', []))];
    }

    /**
     * Resolve media-library ids to the relative disk paths these columns store.
     *
     * The picker hands back only an id; `featured_image` / `gallery` hold a path
     * that the public views turn into a URL via Storage::url(), so the id has to
     * be translated here rather than persisted as-is.
     *
     * @param  mixed  $ids
     * @return array<int, string>
     */
    private function mediaLibraryPaths($ids): array
    {
        $ids = array_filter(array_map('intval', (array) $ids));

        if ($ids === []) {
            return [];
        }

        // Library originals live on the private disk and are encrypted at rest,
        // so their `path` is unreadable through Storage::url(). Each pick has to
        // be materialised into a public copy (with derived sizes) instead.
        return array_values(array_filter(array_map(
            fn (int $id): ?string => $this->images->store($id, 'seerah-events/gallery', 'seerah-events'),
            $ids
        )));
    }

    /**
     * The featured image is either a fresh upload or a media-library pick.
     * An upload wins when both are submitted.
     */
    private function resolveFeaturedImagePath(Request $request, ?UploadedFile $upload): ?string
    {
        if ($upload !== null) {
            return $this->images->store($upload, 'seerah-events/featured-images', 'seerah-events');
        }

        $id = (int) $request->input('featured_image_id');

        return $id > 0
            ? $this->images->store($id, 'seerah-events/featured-images', 'seerah-events')
            : null;
    }

    /**
     * Delete a superseded featured image.
     *
     * Only files this module uploaded (under seerah-events/) are removed. Once
     * the media-library picker was wired in, `featured_image` can hold the path
     * of a shared library asset that other content also points at — deleting
     * that on replace would break every other reference to it.
     */
    private function deleteFeaturedImage(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (! Str::startsWith($path, 'seerah-events/')) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
