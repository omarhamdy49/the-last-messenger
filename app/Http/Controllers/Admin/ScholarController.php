<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\HadithNarrator;
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
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Display a listing of scholars
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $filters = $this->getFilters($request, [
            'region',
            'school',
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        // Using HadithNarrator model to represent scholars for now
        $query = HadithNarrator::query();

        // Apply filters
        if (isset($filters['region'])) {
            $query->where('region', $filters['region']);
        }

        if (isset($filters['school'])) {
            $query->where('school', $filters['school']);
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%")
                  ->orWhere('full_name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('full_name_en', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($pagination['sort'], $pagination['order']);

        $scholars = $query->paginate($pagination['per_page']);

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

            // Create scholar using HadithNarrator model
            $scholar = HadithNarrator::create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? null,
                'full_name_ar' => $validated['full_name_ar'] ?? null,
                'full_name_en' => $validated['full_name_en'] ?? null,
                'kunya' => $validated['kunya'] ?? null,
                'biography' => $validated['biography'] ?? null,
                'reliability_grade' => $validated['reliability_grade'] ?? null,
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
                return $this->createdResponse(
                    $scholar,
                    __('admin.scholar_created')
                );
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_created'));

        } catch (\Exception $e) {
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
    public function show(HadithNarrator $scholar): View|JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $scholar,
                __('admin.scholar_fetched')
            );
        }

        return view('admin.hadith.scholars.show', compact('scholar'));
    }

    /**
     * Show the form for editing the scholar
     */
    public function edit(HadithNarrator $scholar): View
    {
        $this->authorizeAdmin('edit scholars');

        return view('admin.hadith.scholars.edit', compact('scholar'));
    }

    /**
     * Update the specified scholar
     */
    public function update(UpdateScholarRequest $request, HadithNarrator $scholar): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit scholars');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Update scholar
            $scholar->update([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? $scholar->name_en,
                'full_name_ar' => $validated['full_name_ar'] ?? $scholar->full_name_ar,
                'full_name_en' => $validated['full_name_en'] ?? $scholar->full_name_en,
                'kunya' => $validated['kunya'] ?? $scholar->kunya,
                'biography' => $validated['biography'] ?? $scholar->biography,
                'reliability_grade' => $validated['reliability_grade'] ?? $scholar->reliability_grade,
                'birth_year_hijri' => $validated['birth_year_hijri'] ?? $scholar->birth_year_hijri,
                'death_year_hijri' => $validated['death_year_hijri'] ?? $scholar->death_year_hijri,
                'school' => $validated['school'] ?? $scholar->school,
                'region' => $validated['region'] ?? $scholar->region,
            ]);

            DB::commit();

            $this->logActivity('updated', 'Scholar', $scholar->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $scholar,
                    __('admin.scholar_updated')
                );
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_updated'));

        } catch (\Exception $e) {
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
    public function destroy(HadithNarrator $scholar): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete scholars');

        try {
            $scholar->delete();

            $this->logActivity('deleted', 'Scholar', $scholar->id, [
                'name' => $scholar->name_ar ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, __('admin.scholar_deleted'));
            }

            return redirect()
                ->route('admin.hadith.scholars.index')
                ->with('success', __('admin.scholar_deleted'));

        } catch (\Exception $e) {
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
            'ids.*' => 'exists:hadith_narrators,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $scholar = HadithNarrator::findOrFail($id);
            $scholar->delete();
        });

        return $this->successResponse($results, __('admin.scholar_bulk_deleted', ['count' => $results['success']]));
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $search = $request->get('q', '');

        $scholars = HadithNarrator::where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name_ar', 'name_en', 'school', 'region']);

        return $this->successResponse($scholars, __('admin.scholar_fetched'));
    }
}
