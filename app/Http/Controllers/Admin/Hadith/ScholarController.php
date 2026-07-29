<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Repositories\ScholarRepositoryInterface;
use App\Http\Controllers\Admin\AdminController;
use App\Http\Requests\Admin\Hadith\StoreScholarRequest;
use App\Http\Requests\Admin\Hadith\UpdateScholarRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Scholar Controller
 * Manages Islamic scholars (علماء) - uses HadithNarrator model for now
 * TODO: Create dedicated Scholar model in future iterations
 */
class ScholarController extends AdminController
{
    public function __construct(
        private readonly ScholarRepositoryInterface $scholarRepository
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of scholars
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $filters = $this->getFilters($request, ['region', 'school']);
        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $scholars = $this->scholarRepository->search($search, $pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($scholars, __('admin.scholar_fetched'));
        }

        return view('admin.hadith.scholars.index', compact('scholars'));
    }

    /**
     * Show the form for creating a new scholar
     */
    public function create(): View
    {
        $this->authorizeAdmin('create scholars');

        return view('admin.hadith.scholars.create');
    }

    /**
     * Store a newly created scholar
     */
    public function store(StoreScholarRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create scholars');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $scholar = $this->scholarRepository->create([
                'name' => array_filter(['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? null]),
                'full_name' => array_filter(['ar' => $validated['full_name_ar'] ?? null, 'en' => $validated['full_name_en'] ?? null]),
                'kunya' => array_filter(['ar' => $validated['kunya'] ?? null]),
                'biography' => array_filter(['ar' => $validated['biography'] ?? null]),
                'birth_year_hijri' => $validated['birth_year_hijri'] ?? null,
                'death_year_hijri' => $validated['death_year_hijri'] ?? null,
                'school' => $validated['school'] ?? null,
                'region' => $validated['region'] ?? null,
            ]);

            DB::commit();

            $this->logActivity('created', 'Scholar', $scholar->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse($scholar, __('admin.scholar_created'));
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_created'));

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.scholar_create_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.scholar_create_failed')]);
        }
    }

    /**
     * Display the specified scholar
     */
    public function show(int $id): View|JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $scholar = $this->scholarRepository->findOrFail($id);

        if ($this->expectsJson(request())) {
            return $this->successResponse($scholar, __('admin.scholar_fetched'));
        }

        return view('admin.hadith.scholars.show', compact('scholar'));
    }

    /**
     * Show the form for editing the scholar
     */
    public function edit(int $id): View
    {
        $this->authorizeAdmin('edit scholars');

        $scholar = $this->scholarRepository->findOrFail($id);

        return view('admin.hadith.scholars.edit', compact('scholar'));
    }

    /**
     * Update the specified scholar
     */
    public function update(UpdateScholarRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit scholars');

        DB::beginTransaction();
        try {
            $validated = $request->validated();
            $scholar = $this->scholarRepository->findOrFail($id);

            $this->scholarRepository->update($id, [
                'name' => array_filter(['ar' => $validated['name_ar'], 'en' => $validated['name_en'] ?? null]),
                'full_name' => array_filter(['ar' => $validated['full_name_ar'] ?? null, 'en' => $validated['full_name_en'] ?? null]),
                'kunya' => array_filter(['ar' => $validated['kunya'] ?? null]),
                'biography' => array_filter(['ar' => $validated['biography'] ?? null]),
                'birth_year_hijri' => $validated['birth_year_hijri'] ?? $scholar->birth_year_hijri,
                'death_year_hijri' => $validated['death_year_hijri'] ?? $scholar->death_year_hijri,
                'school' => $validated['school'] ?? $scholar->school,
                'region' => $validated['region'] ?? $scholar->region,
            ]);

            $scholar = $this->scholarRepository->findOrFail($id);

            DB::commit();

            $this->logActivity('updated', 'Scholar', $scholar->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse($scholar, __('admin.scholar_updated'));
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_updated'));

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.scholar_update_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.scholar_update_failed')]);
        }
    }

    /**
     * Remove the specified scholar
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete scholars');

        try {
            $scholar = $this->scholarRepository->findOrFail($id);

            $this->scholarRepository->delete($id);

            $this->logActivity('deleted', 'Scholar', $scholar->id, [
                'name' => $scholar->name_ar ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, __('admin.scholar_deleted'));
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_deleted'));

        } catch (\Throwable $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.scholar_delete_failed'));
            }

            return back()
                ->withErrors(['error' => __('admin.scholar_delete_failed')]);
        }
    }

    /**
     * Bulk delete scholars
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete scholars');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:hadith_scholars,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $this->scholarRepository->delete((int) $id);
        });

        return $this->successResponse(
            $results,
            __('admin.scholar_bulk_deleted', ['count' => $results['success']])
        );
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $search = $request->get('q', '');

        $scholars = $this->scholarRepository->search($search, 10);

        return $this->successResponse($scholars->items(), __('admin.scholar_fetched'));
    }
}
