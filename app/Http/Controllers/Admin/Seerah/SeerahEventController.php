<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Seerah;

use App\Domain\Seerah\DTOs\CreateSeerahEventData;
use App\Domain\Seerah\DTOs\UpdateSeerahEventData;
use App\Domain\Seerah\Enums\SeerahEventStatus;
use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Seerah\Services\SeerahEventTypeService;
use App\Domain\Seerah\Services\SeerahMilestoneService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventRequest;
use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use App\Repositories\ArticleRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        protected ArticleRepository $articleRepository
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
            'search' => $request->string('search')->toString() ?: null,
        ]);

        $events = $this->seerahEventService->paginateWithFilters($filters, $perPage);

        // Single aggregate query delegated to repository via service
        $stats = $this->seerahEventService->getIndexStats();
        $eventTypes = $this->eventTypeService->getForDropdown();
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

        return view('admin.seerah-events.index', compact('events', 'stats', 'eventTypes', 'statuses'));
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

        return view('admin.seerah-events.create', compact('articles', 'eventTypes', 'milestones', 'statuses'));
    }

    /**
     * Store a newly created Seerah event
     *
     * POST /su/portal/v1/admin/seerah-events
     */
    public function store(StoreSeerahEventRequest $request): RedirectResponse
    {
        $dto = CreateSeerahEventData::fromRequest($request);

        // Store featured image to disk before passing path to service
        $storedImagePath = null;
        if ($dto->featuredImage !== null) {
            $storedImagePath = $dto->featuredImage->store('seerah-events/featured-images', 'public');
        }

        $event = $this->seerahEventService->createEvent($dto);

        // Persist stored image path if a file was uploaded
        if ($storedImagePath !== null) {
            $event->update(['featured_image' => $storedImagePath]);
        }

        // Sync translations
        $event->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
            'content' => $dto->content,
        ]);

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

        $event->loadMissing(['translations', 'article.locales:id,article_id,locale,title,slug,is_ready']);
        $articles = $this->articleRepository->getForDropdown();
        $eventTypes = $this->eventTypeService->getForDropdown();
        $milestones = $this->milestoneService->getForDropdown();
        $statuses = SeerahEventStatus::cases();

        return view('admin.seerah-events.edit', compact('event', 'articles', 'eventTypes', 'milestones', 'statuses'));
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

        $dto = UpdateSeerahEventData::fromRequest($request);

        // Store featured image to disk before passing path to service
        $storedImagePath = null;
        if ($dto->featuredImage !== null) {
            $this->deleteFeaturedImage($oldEvent->featured_image);
            $storedImagePath = $dto->featuredImage->store('seerah-events/featured-images', 'public');
        }

        // Perform update via DTO-typed service method
        $event = $this->seerahEventService->updateEvent($id, $dto);

        // Persist stored image path if a file was uploaded
        if ($storedImagePath !== null) {
            $event->update(['featured_image' => $storedImagePath]);
        }

        // Sync translations
        $event->syncTranslations([
            'title' => $dto->title,
            'description' => $dto->description,
            'content' => $dto->content,
        ]);

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
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Seerah\Models\SeerahEvent::class,
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
            \App\Domain\Seerah\Models\SeerahEvent::class,
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

    private function deleteFeaturedImage(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        if (Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }
}
