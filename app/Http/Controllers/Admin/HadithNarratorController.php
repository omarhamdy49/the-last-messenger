<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Services\HadithNarratorService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreHadithNarratorRequest;
use App\Http\Requests\Admin\UpdateHadithNarratorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Hadith Narrator Controller
 * Handles hadith narrator management for admin portal
 */
class HadithNarratorController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected HadithNarratorService $narratorService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all hadith narrators
     *
     * GET /su/portal/v1/admin/hadith-narrators
     */
    public function index(Request $request): View|JsonResponse
    {
        $perPage = $request->integer('per_page', 15);

        // Build filters
        $filters = [];
        if ($request->filled('grade')) {
            $filters['grade'] = $request->input('grade');
        }
        if ($request->filled('era')) {
            $filters['era'] = $request->input('era');
        }
        if ($request->filled('country')) {
            $filters['country'] = $request->input('country');
        }
        if ($request->filled('status')) {
            $filters['status'] = $request->input('status');
        }
        if ($request->filled('search')) {
            $filters['search'] = $request->input('search');
        }

        $narrators = $this->narratorService->paginate($perPage, $filters);

        $stats = [
            'total' => HadithNarrator::count(),
            'active' => HadithNarrator::where('status', 'active')->count(),
            'trustworthy' => HadithNarrator::trustworthy()->count(),
            'by_grade' => $this->narratorService->countByGrade(),
            'by_era' => $this->narratorService->countByEra(),
        ];

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => $narrators->items(),
                'stats' => $stats,
                'meta' => [
                    'current_page' => $narrators->currentPage(),
                    'per_page' => $narrators->perPage(),
                    'total' => $narrators->total(),
                    'last_page' => $narrators->lastPage(),
                ],
            ]);
        }

        return view('admin.hadith-narrators.index', compact('narrators', 'stats'));
    }

    /**
     * Show the form for creating a new narrator
     *
     * GET /su/portal/v1/admin/hadith-narrators/create
     */
    public function create(): View
    {
        $grades = $this->narratorService->getReliabilityGrades();
        $eras = $this->narratorService->getEras();
        $allNarrators = $this->narratorService->getActive();

        return view('admin.hadith-narrators.create', compact('grades', 'eras', 'allNarrators'));
    }

    /**
     * Store a newly created narrator
     *
     * POST /su/portal/v1/admin/hadith-narrators
     */
    public function store(StoreHadithNarratorRequest $request): RedirectResponse|JsonResponse
    {
        $narrator = $this->narratorService->create($request->validated());

        // Log the creation
        $this->logger->logCreate($narrator, $request->user());

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء الراوي بنجاح',
                'data' => $narrator,
            ], 201);
        }

        return redirect()
            ->route('admin.hadith-narrators.index')
            ->with('success', 'تم إنشاء الراوي بنجاح');
    }

    /**
     * Display the specified narrator
     *
     * GET /su/portal/v1/admin/hadith-narrators/{id}
     */
    public function show(int $id): View|JsonResponse
    {
        $narrator = $this->narratorService->getWithDetails($id);

        if (! $narrator) {
            abort(404, 'Narrator not found');
        }

        $hadithCount = $this->narratorService->getHadithCount($id);
        $teachers = $this->narratorService->getTeachers($id);
        $students = $this->narratorService->getStudents($id);

        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'data' => [
                    'narrator' => $narrator,
                    'hadith_count' => $hadithCount,
                    'teachers' => $teachers,
                    'students' => $students,
                ],
            ]);
        }

        return view('admin.hadith-narrators.show', compact('narrator', 'hadithCount', 'teachers', 'students'));
    }

    /**
     * Show the form for editing the specified narrator
     *
     * GET /su/portal/v1/admin/hadith-narrators/{id}/edit
     */
    public function edit(int $id): View
    {
        $narrator = $this->narratorService->getWithDetails($id);

        if (! $narrator) {
            abort(404, 'Narrator not found');
        }

        $grades = $this->narratorService->getReliabilityGrades();
        $eras = $this->narratorService->getEras();
        $allNarrators = $this->narratorService->getActive();

        return view('admin.hadith-narrators.edit', compact('narrator', 'grades', 'eras', 'allNarrators'));
    }

    /**
     * Update the specified narrator
     *
     * PUT/PATCH /su/portal/v1/admin/hadith-narrators/{id}
     */
    public function update(UpdateHadithNarratorRequest $request, int $id): RedirectResponse|JsonResponse
    {
        // Get old values before update
        $oldNarrator = $this->narratorService->getWithDetails($id);

        if (! $oldNarrator) {
            abort(404, 'Narrator not found');
        }

        $oldValues = $oldNarrator->toArray();

        // Perform update
        $narrator = $this->narratorService->update($oldNarrator, $request->validated());

        // Log the update with diff
        $this->logger->logUpdate($narrator, $oldValues, $narrator->toArray(), $request->user());

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'تم تحديث الراوي بنجاح',
                'data' => $narrator,
            ]);
        }

        return redirect()
            ->route('admin.hadith-narrators.index')
            ->with('success', 'تم تحديث الراوي بنجاح');
    }

    /**
     * Remove the specified narrator
     *
     * DELETE /su/portal/v1/admin/hadith-narrators/{id}
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        // Get model before deletion
        $narrator = $this->narratorService->getWithDetails($id);

        if (! $narrator) {
            abort(404, 'Narrator not found');
        }

        // Perform deletion
        $this->narratorService->delete($narrator);

        // Log the deletion
        $this->logger->logDelete($narrator, request()->user());

        // Return JSON for AJAX requests
        if (request()->wantsJson() || request()->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => 'تم حذف الراوي بنجاح',
            ], 204);
        }

        return redirect()
            ->route('admin.hadith-narrators.index')
            ->with('success', 'تم حذف الراوي بنجاح');
    }

    /**
     * Bulk delete narrators
     *
     * POST /su/portal/v1/admin/hadith-narrators/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse|JsonResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:hadith_narrators,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = $this->narratorService->bulkDelete($ids);

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            HadithNarrator::class,
            $ids,
            $request->user()
        );

        // Return JSON for AJAX requests
        if ($request->wantsJson() || $request->header('X-Requested-With') === 'XMLHttpRequest') {
            return response()->json([
                'success' => true,
                'message' => "تم حذف {$deleted} راوٍ بنجاح",
                'deleted_count' => $deleted,
            ]);
        }

        return redirect()
            ->route('admin.hadith-narrators.index')
            ->with('success', "تم حذف {$deleted} راوٍ بنجاح");
    }

    /**
     * Search narrators (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/search
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
        ]);

        $query = $request->input('q');
        $narrators = $this->narratorService->search($query);

        return response()->json([
            'success' => true,
            'data' => $narrators,
        ]);
    }

    /**
     * Get narrators by grade (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/by-grade/{grade}
     */
    public function byGrade(Request $request, string $grade): JsonResponse
    {
        $narrators = $this->narratorService->getByGrade($grade);

        return response()->json([
            'success' => true,
            'data' => $narrators,
        ]);
    }

    /**
     * Get narrators by era (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/by-era/{era}
     */
    public function byEra(Request $request, string $era): JsonResponse
    {
        $narrators = $this->narratorService->getByEra($era);

        return response()->json([
            'success' => true,
            'data' => $narrators,
        ]);
    }

    /**
     * Get narrators by country (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/by-country/{country}
     */
    public function byCountry(Request $request, string $country): JsonResponse
    {
        $narrators = $this->narratorService->getByCountry($country);

        return response()->json([
            'success' => true,
            'data' => $narrators,
        ]);
    }

    /**
     * Get narrator's teachers (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/{id}/teachers
     */
    public function teachers(int $id): JsonResponse
    {
        $teachers = $this->narratorService->getTeachers($id);

        return response()->json([
            'success' => true,
            'data' => $teachers,
        ]);
    }

    /**
     * Get narrator's students (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/hadith-narrators/{id}/students
     */
    public function students(int $id): JsonResponse
    {
        $students = $this->narratorService->getStudents($id);

        return response()->json([
            'success' => true,
            'data' => $students,
        ]);
    }
}
