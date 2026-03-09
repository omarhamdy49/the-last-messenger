<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Seerah\Services\SeerahEventService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSeerahEventRequest;
use App\Http\Requests\Admin\UpdateSeerahEventRequest;
use App\Models\Article;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all Seerah events
     *
     * GET /su/portal/v1/admin/seerah-events
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);
        $events = $this->seerahEventService->paginate($perPage, ['*'], []);

        // Single aggregate query to replace N+1 count-per-category queries
        $eventCountRows = DB::table('seerah_events')
            ->selectRaw('event_type, COUNT(*) as count')
            ->whereNull('deleted_at')
            ->groupBy('event_type')
            ->pluck('count', 'event_type');

        $stats = [
            'total'      => $eventCountRows->sum(),
            'categories' => $eventCountRows->toArray(),
        ];

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

        return view('admin.seerah-events.index', compact('events', 'stats'));
    }

    /**
     * Show the form for creating a new Seerah event
     *
     * GET /su/portal/v1/admin/seerah-events/create
     */
    public function create(): View
    {
        $articles = Article::query()
            ->with('locales')
            ->orderByDesc('id')
            ->get();

        return view('admin.seerah-events.create', compact('articles'));
    }

    /**
     * Store a newly created Seerah event
     *
     * POST /su/portal/v1/admin/seerah-events
     */
    public function store(StoreSeerahEventRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // Handle is_featured checkbox (unchecked checkboxes are not sent)
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('featured_image')) {
            $data['featured_image'] = $request->file('featured_image')
                ->store('seerah-events/featured-images', 'public');
        }

        $event = $this->seerahEventService->create($data);

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

        $event->loadMissing(['article.locales', 'article.mediaItems', 'participants']);

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

        $event->loadMissing('article.locales');
        $articles = Article::query()
            ->with('locales')
            ->orderByDesc('id')
            ->get();

        return view('admin.seerah-events.edit', compact('event', 'articles'));
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

        $data = $request->validated();

        // Handle is_featured checkbox (unchecked checkboxes are not sent)
        $data['is_featured'] = $request->boolean('is_featured');

        if ($request->hasFile('featured_image')) {
            $this->deleteFeaturedImage($oldEvent->featured_image);
            $data['featured_image'] = $request->file('featured_image')
                ->store('seerah-events/featured-images', 'public');
        }

        // Perform update
        $this->seerahEventService->update($id, $data);

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

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $event = $this->seerahEventService->findOrFail((int) $id);
                $this->deleteFeaturedImage($event->featured_image);
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
