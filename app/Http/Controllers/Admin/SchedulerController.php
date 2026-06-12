<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Shared\Enums\ScheduleAction;
use App\Domain\Shared\Services\SchedulerService;
use App\Exceptions\Domain\BusinessLogicException;
use App\Exceptions\Domain\ResourceNotFoundException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ScheduleContentRequest;
use App\Http\Requests\UpdateScheduleRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Scheduler Controller
 * Manages content scheduling for admin portal
 */
class SchedulerController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected SchedulerService $schedulerService
    ) {}

    /**
     * Display a listing of scheduled content
     *
     * GET /su/portal/v1/admin/scheduler
     */
    public function index(Request $request): View|JsonResponse
    {
        // Build filters
        $filters = [
            'status' => $request->input('status'),
            'action' => $request->input('action'),
            'type' => $request->input('type'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'created_by' => $request->input('created_by'),
            'recurring' => $request->boolean('recurring'),
            'per_page' => $request->integer('per_page', 15),
        ];

        // Get schedules with filters
        $hasFilters = array_filter($filters, fn ($value) => ! is_null($value) && $value !== '');
        $schedules = ! empty($hasFilters)
            ? $this->schedulerService->searchSchedules($filters)
            : $this->schedulerService->getAllSchedules($filters['per_page']);

        // Get statistics
        $stats = $this->schedulerService->getStatistics();

        // Get upcoming schedules for sidebar
        $upcoming = $this->schedulerService->getUpcomingSchedules(5);

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $schedules->items(),
                'stats' => $stats,
                'upcoming' => $upcoming,
                'meta' => [
                    'current_page' => $schedules->currentPage(),
                    'per_page' => $schedules->perPage(),
                    'total' => $schedules->total(),
                    'last_page' => $schedules->lastPage(),
                ],
            ]);
        }

        // Available content types for filter
        $contentTypes = [
            'App\\Domain\\Blog\\Models\\Article' => 'مقال',
            'App\\Domain\\Hadith\\Models\\Hadith' => 'حديث',
            'App\\Domain\\Media\\Models\\MediaProgram' => 'برنامج',
            'App\\Domain\\Media\\Models\\MediaEpisode' => 'حلقة',
            'App\\Domain\\Seerah\\Models\\SeerahEvent' => 'حدث سيرة',
            'App\\Domain\\Sahaba\\Models\\Sahabi' => 'صحابي',
            'App\\Domain\\Quran\\Models\\QuranVerse' => 'آية',
            'App\\Domain\\Quran\\Models\\QuranSurah' => 'سورة',
        ];

        return view('admin.scheduler.index', compact(
            'schedules',
            'stats',
            'upcoming',
            'contentTypes',
            'filters'
        ));
    }

    /**
     * Show the form for creating a new schedule
     *
     * GET /su/portal/v1/admin/scheduler/create
     */
    public function create(Request $request): View
    {
        // Get content type and ID from query parameters
        $contentType = $request->query('type');
        $contentId = $request->query('id');

        // Available content types
        $contentTypes = [
            'App\\Domain\\Blog\\Models\\Article' => 'مقال',
            'App\\Domain\\Hadith\\Models\\Hadith' => 'حديث',
            'App\\Domain\\Media\\Models\\MediaProgram' => 'برنامج',
            'App\\Domain\\Media\\Models\\MediaEpisode' => 'حلقة',
            'App\\Domain\\Seerah\\Models\\SeerahEvent' => 'حدث سيرة',
            'App\\Domain\\Sahaba\\Models\\Sahabi' => 'صحابي',
            'App\\Domain\\Quran\\Models\\QuranVerse' => 'آية',
            'App\\Domain\\Quran\\Models\\QuranSurah' => 'سورة',
        ];

        // Available timezones
        $timezones = timezone_identifiers_list();

        return view('admin.scheduler.create', compact(
            'contentTypes',
            'timezones',
            'contentType',
            'contentId'
        ));
    }

    /**
     * Store a newly created schedule
     *
     * POST /su/portal/v1/admin/scheduler
     */
    public function store(ScheduleContentRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $validated = $request->validated();

            // Get the content model
            $contentClass = $validated['schedulable_type'];
            $content = $contentClass::findOrFail($validated['schedulable_id']);

            // Get current user
            $userId = auth()->id();

            // Schedule based on action
            $schedule = match (ScheduleAction::from($validated['action'])) {
                ScheduleAction::PUBLISH => $this->schedulerService->schedulePublish(
                    $content,
                    $validated['scheduled_at'],
                    $userId,
                    $validated['timezone'] ?? 'UTC',
                    $validated['notes'] ?? null,
                    $validated['recurrence'] ?? null
                ),
                ScheduleAction::UNPUBLISH => $this->schedulerService->scheduleUnpublish(
                    $content,
                    $validated['scheduled_at'],
                    $userId,
                    $validated['timezone'] ?? 'UTC',
                    $validated['notes'] ?? null
                ),
                ScheduleAction::UPDATE => $this->schedulerService->scheduleUpdate(
                    $content,
                    $validated['scheduled_data'] ?? [],
                    $validated['scheduled_at'],
                    $userId,
                    $validated['timezone'] ?? 'UTC',
                    $validated['notes'] ?? null
                ),
            };

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إنشاء الجدولة بنجاح',
                    'data' => $schedule,
                ], 201);
            }

            return redirect()->route('admin.scheduler.show', $schedule->id)
                ->with('success', 'تم إنشاء الجدولة بنجاح');
        } catch (BusinessLogicException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        } catch (\Throwable $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'حدث خطأ أثناء إنشاء الجدولة',
                ], 500);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => 'حدث خطأ أثناء إنشاء الجدولة']);
        }
    }

    /**
     * Display the specified schedule
     *
     * GET /su/portal/v1/admin/scheduler/{id}
     */
    public function show(int $id): View|JsonResponse
    {
        try {
            $schedule = $this->schedulerService->getScheduleById($id);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'data' => $schedule,
                ]);
            }

            return view('admin.scheduler.show', compact('schedule'));
        } catch (ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified schedule
     *
     * GET /su/portal/v1/admin/scheduler/{id}/edit
     */
    public function edit(int $id): View
    {
        try {
            $schedule = $this->schedulerService->getScheduleById($id);

            if (! $schedule->canBeUpdated()) {
                return redirect()->route('admin.scheduler.show', $id)
                    ->withErrors(['error' => 'لا يمكن تعديل هذه الجدولة في حالتها الحالية']);
            }

            // Available timezones
            $timezones = timezone_identifiers_list();

            return view('admin.scheduler.edit', compact('schedule', 'timezones'));
        } catch (ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        }
    }

    /**
     * Update the specified schedule
     *
     * PUT /su/portal/v1/admin/scheduler/{id}
     */
    public function update(int $id, UpdateScheduleRequest $request): RedirectResponse|JsonResponse
    {
        try {
            $schedule = $this->schedulerService->updateSchedule($id, $request->validated());

            if ($request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم تحديث الجدولة بنجاح',
                    'data' => $schedule,
                ]);
            }

            return redirect()->route('admin.scheduler.show', $id)
                ->with('success', 'تم تحديث الجدولة بنجاح');
        } catch (ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (BusinessLogicException $e) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Cancel the specified schedule
     *
     * DELETE /su/portal/v1/admin/scheduler/{id}
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        try {
            $this->schedulerService->cancelSchedule($id);

            if (request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'تم إلغاء الجدولة بنجاح',
                ]);
            }

            return redirect()->route('admin.scheduler.index')
                ->with('success', 'تم إلغاء الجدولة بنجاح');
        } catch (ResourceNotFoundException $e) {
            abort(404, $e->getMessage());
        } catch (BusinessLogicException $e) {
            if (request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return redirect()->back()
                ->withErrors(['error' => $e->getMessage()]);
        }
    }

    /**
     * Get schedule history for specific content
     *
     * GET /su/portal/v1/admin/scheduler/{type}/{id}/history
     */
    public function history(string $type, int $id): View|JsonResponse
    {
        // Decode the type (URL encoded)
        $contentType = urldecode($type);

        $history = $this->schedulerService->getScheduleHistory($contentType, $id);

        if (request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $history->items(),
                'meta' => [
                    'current_page' => $history->currentPage(),
                    'per_page' => $history->perPage(),
                    'total' => $history->total(),
                    'last_page' => $history->lastPage(),
                ],
            ]);
        }

        return view('admin.scheduler.history', compact('history', 'contentType', 'id'));
    }
}
