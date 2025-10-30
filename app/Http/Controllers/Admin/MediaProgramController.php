<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Media\Services\MediaProgramService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreMediaProgramRequest;
use App\Http\Requests\Admin\UpdateMediaProgramRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Media Program Controller
 * Handles media program management for admin portal
 */
class MediaProgramController extends Controller
{
    /**
     * Constructor
     */
    public function __construct(
        protected MediaProgramService $mediaProgramService,
        protected AdminLoggerService $logger
    ) {}

    /**
     * Display a listing of all media programs
     *
     * GET /su/portal/v1/admin/media-programs
     */
    public function index(Request $request): View
    {
        $perPage = $request->integer('per_page', 50);

        $programs = $this->mediaProgramService->paginate(
            $perPage,
            ['*'],
            ['episodes', 'category']
        );

        $stats = [
            'total' => $this->mediaProgramService->count(),
        ];

        return view('admin.media.index', compact('programs', 'stats'));
    }

    /**
     * Show the form for creating a new media program
     *
     * GET /su/portal/v1/admin/media-programs/create
     */
    public function create(): View
    {
        return view('admin.media.create');
    }

    /**
     * Store a newly created media program
     *
     * POST /su/portal/v1/admin/media-programs
     */
    public function store(StoreMediaProgramRequest $request): RedirectResponse
    {
        $program = $this->mediaProgramService->create($request->validated());

        // Log the creation
        $this->logger->logCreate($program, $request->user());

        return redirect()
            ->route('admin.media-programs.index')
            ->with('success', 'تم إنشاء البرنامج بنجاح');
    }

    /**
     * Display the specified media program
     *
     * GET /su/portal/v1/admin/media-programs/{id}
     */
    public function show(int $id): View
    {
        $program = $this->mediaProgramService->find($id, ['episodes', 'category']);

        if (! $program) {
            abort(404, 'Media program not found');
        }

        return view('admin.media.show', compact('program'));
    }

    /**
     * Show the form for editing the specified media program
     *
     * GET /su/portal/v1/admin/media-programs/{id}/edit
     */
    public function edit(int $id): View
    {
        $program = $this->mediaProgramService->find($id, ['episodes', 'category']);

        if (! $program) {
            abort(404, 'Media program not found');
        }

        return view('admin.media.edit', compact('program'));
    }

    /**
     * Update the specified media program
     *
     * PUT/PATCH /su/portal/v1/admin/media-programs/{id}
     */
    public function update(UpdateMediaProgramRequest $request, int $id): RedirectResponse
    {
        // Get old values before update
        $oldProgram = $this->mediaProgramService->find($id);

        if (! $oldProgram) {
            abort(404, 'Media program not found');
        }

        $oldValues = $oldProgram->toArray();

        // Perform update
        $this->mediaProgramService->update($id, $request->validated());

        $program = $this->mediaProgramService->find($id, ['episodes']);

        // Log the update with diff
        $this->logger->logUpdate($program, $oldValues, $program->toArray(), $request->user());

        return redirect()
            ->route('admin.media-programs.index')
            ->with('success', 'تم تحديث البرنامج بنجاح');
    }

    /**
     * Remove the specified media program
     *
     * DELETE /su/portal/v1/admin/media-programs/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        // Get model before deletion
        $program = $this->mediaProgramService->find($id);

        if (! $program) {
            abort(404, 'Media program not found');
        }

        // Perform deletion
        $this->mediaProgramService->delete($id);

        // Log the deletion
        $this->logger->logDelete($program, request()->user());

        return redirect()
            ->route('admin.media-programs.index')
            ->with('success', 'تم حذف البرنامج بنجاح');
    }

    /**
     * Bulk delete media programs
     *
     * POST /su/portal/v1/admin/media-programs/bulk-delete
     */
    public function bulkDelete(Request $request): RedirectResponse
    {
        $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:media_programs,id'],
        ]);

        $ids = $request->input('ids');
        $deleted = 0;
        foreach ($ids as $id) {
            try {
                $this->mediaProgramService->delete($id);
                $deleted++;
            } catch (\Exception $e) {
                // Continue deleting
            }
        }

        // Log bulk action
        $this->logger->logBulkAction(
            'bulk_delete',
            \App\Domain\Media\Models\MediaProgram::class,
            $ids,
            $request->user()
        );

        return redirect()
            ->route('admin.media-programs.index')
            ->with('success', "تم حذف {$deleted} برنامج بنجاح");
    }

    /**
     * Search media programs (AJAX endpoint)
     *
     * GET /su/portal/v1/admin/media-programs/search
     */
    public function search(Request $request)
    {
        $request->validate([
            'q' => ['required', 'string', 'min:2'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $query = $request->input('q');
        $perPage = $request->integer('per_page', 15);

        $programs = $this->mediaProgramService->search($query, $perPage);

        return response()->json([
            'success' => true,
            'data' => $programs->items(),
            'meta' => [
                'current_page' => $programs->currentPage(),
                'per_page' => $programs->perPage(),
                'total' => $programs->total(),
                'last_page' => $programs->lastPage(),
            ],
        ]);
    }
}
