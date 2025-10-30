<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventRequest;
use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all Seerah events
     *
     * GET /su/portal/v1/admin/seerah-events
     */
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 15);
        $events = $this->seerahEventService->paginate($perPage, ['*'], []);

        $stats = [
            'total' => $this->seerahEventService->count(),
            'categories' => $this->seerahEventService->countByCategory(),
        ];

        return view('admin.seerah.index', compact('events', 'stats'));
    }

    /**
     * Show the form for creating a new Seerah event
     *
     * GET /su/portal/v1/admin/seerah-events/create
     */
    public function create(): View
    {
        return view('admin.seerah.create');
    }

    /**
     * Store a newly created Seerah event
     *
     * POST /su/portal/v1/admin/seerah-events
     */
    public function store(StoreSeerahEventRequest $request): RedirectResponse
    {
        $event = $this->seerahEventService->create($request->validated());

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

        return view('admin.seerah.show', compact('event'));
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

        return view('admin.seerah.edit', compact('event'));
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

        // Perform update
        $this->seerahEventService->update($id, $request->validated());

        $event = $this->seerahEventService->find($id);

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
        // Get model before deletion
        $event = $this->seerahEventService->find($id);

        if (! $event) {
            abort(404, 'Seerah event not found');
        }

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

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->seerahEventService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
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
}
