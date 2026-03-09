<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Enums\HadithGrade;
use App\Domain\Hadith\Exceptions\HadithOperationException;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Repositories\NarratorRepositoryInterface;
use App\Http\Requests\Admin\Hadith\StoreNarratorRequest;
use App\Http\Requests\Admin\Hadith\UpdateNarratorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Narrator Controller
 * Manages Hadith narrators/transmitters with multi-language biographies
 */
class NarratorController extends AdminController
{
    public function __construct(
        protected NarratorRepositoryInterface $repository
    ) {
        parent::__construct();
    }

    /**
     * Display a listing of narrators
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $filters = $this->getFilters($request, [
            'reliability_grade',
            'region',
            'school',
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $query = HadithNarrator::query();

        // Apply filters
        if (isset($filters['reliability_grade'])) {
            $query->where('reliability_grade', $filters['reliability_grade']);
        }

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

        $narrators = $query->paginate($pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($narrators, __('admin.narrator_fetched'));
        }

        $grades = HadithGrade::cases();

        return view('admin.hadith.narrators.index', compact('narrators', 'grades'));
    }

    /**
     * Show the form for creating a new narrator
     */
    public function create(): View
    {
        $this->authorizeAdmin('create narrators');

        $grades = HadithGrade::cases();

        return view('admin.hadith.narrators.create', compact('grades'));
    }

    /**
     * Store a newly created narrator
     */
    public function store(StoreNarratorRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create narrators');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Create narrator
            $narrator = HadithNarrator::create([
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

            $this->logActivity('created', 'HadithNarrator', $narrator->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $narrator,
                    __('admin.narrator_created')
                );
            }

            return redirect()
                ->route('admin.hadith.narrators.index')
                ->with('success', __('admin.narrator_created'));

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('admin.narrator.create_failed', [
                'user_id' => auth()->id(),
                'error'   => $e->getMessage(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.operation_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Display the specified narrator
     */
    public function show(HadithNarrator $narrator): View|JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        // Note: hadiths() returns Collection not Relationship, so loadCount unavailable

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $narrator,
                __('admin.narrator_fetched')
            );
        }

        return view('admin.hadith.narrators.show', compact('narrator'));
    }

    /**
     * Show the form for editing the narrator
     */
    public function edit(HadithNarrator $narrator): View
    {
        $this->authorizeAdmin('edit narrators');

        $grades = HadithGrade::cases();

        return view('admin.hadith.narrators.edit', compact('narrator', 'grades'));
    }

    /**
     * Update the specified narrator
     */
    public function update(UpdateNarratorRequest $request, HadithNarrator $narrator): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit narrators');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Update narrator
            $narrator->update([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? $narrator->name_en,
                'full_name_ar' => $validated['full_name_ar'] ?? $narrator->full_name_ar,
                'full_name_en' => $validated['full_name_en'] ?? $narrator->full_name_en,
                'kunya' => $validated['kunya'] ?? $narrator->kunya,
                'biography' => $validated['biography'] ?? $narrator->biography,
                'reliability_grade' => $validated['reliability_grade'] ?? $narrator->reliability_grade,
                'birth_year_hijri' => $validated['birth_year_hijri'] ?? $narrator->birth_year_hijri,
                'death_year_hijri' => $validated['death_year_hijri'] ?? $narrator->death_year_hijri,
                'school' => $validated['school'] ?? $narrator->school,
                'region' => $validated['region'] ?? $narrator->region,
            ]);

            DB::commit();

            $this->logActivity('updated', 'HadithNarrator', $narrator->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $narrator,
                    __('admin.narrator_updated')
                );
            }

            return redirect()
                ->route('admin.hadith.narrators.index')
                ->with('success', __('admin.narrator_updated'));

        } catch (\Throwable $e) {
            DB::rollBack();

            \Log::error('admin.narrator.update_failed', [
                'user_id'     => auth()->id(),
                'narrator_id' => $narrator->id,
                'error'       => $e->getMessage(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.operation_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Soft delete the specified narrator
     */
    public function destroy(HadithNarrator $narrator): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete narrators');

        try {
            // Check if narrator is used in any hadith
            if ($narrator->hadiths()->count() > 0) {
                if ($this->expectsJson(request())) {
                    return $this->errorResponse(__('admin.narrator_has_hadiths'), 422);
                }

                return back()->withErrors(['error' => __('admin.narrator_has_hadiths')]);
            }

            $narrator->delete();

            $this->logActivity('deleted', 'HadithNarrator', $narrator->id, [
                'name' => $narrator->name_ar ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, __('admin.narrator_deleted'));
            }

            return redirect()
                ->route('admin.hadith.narrators.index')
                ->with('success', __('admin.narrator_deleted'));

        } catch (\Throwable $e) {
            \Log::error('admin.narrator.delete_failed', [
                'user_id'     => auth()->id(),
                'narrator_id' => $narrator->id,
                'error'       => $e->getMessage(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.operation_failed'));
            }

            return back()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Restore a soft-deleted narrator
     */
    public function restore(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit narrators');

        try {
            $narrator = HadithNarrator::withTrashed()->findOrFail($id);
            $narrator->restore();

            $this->logActivity('restored', 'HadithNarrator', $narrator->id);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $narrator,
                    __('admin.narrator_restored')
                );
            }

            return redirect()
                ->route('admin.hadith.narrators.index')
                ->with('success', __('admin.narrator_restored'));

        } catch (\Throwable $e) {
            \Log::error('admin.narrator.restore_failed', [
                'user_id'     => auth()->id(),
                'narrator_id' => $id,
                'error'       => $e->getMessage(),
            ]);

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.operation_failed'));
            }

            return back()
                ->withErrors(['error' => __('admin.operation_failed')]);
        }
    }

    /**
     * Bulk delete narrators
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete narrators');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:hadith_narrators,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $narrator = HadithNarrator::findOrFail($id);

            // Check if narrator is used in any hadith
            if ($narrator->hadiths()->count() > 0) {
                throw new HadithOperationException(
                    operation: 'delete_narrator',
                    reason: __('admin.narrator_has_hadiths'),
                );
            }

            $narrator->delete();
        });

        return $this->successResponse($results, __('admin.narrator_bulk_deleted', ['count' => $results['success']]));
    }

    /**
     * Get narrator statistics
     */
    public function statistics(HadithNarrator $narrator): JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $stats = [
            'hadith_count' => $narrator->hadiths()->count(),
        ];

        return $this->successResponse($stats, __('admin.narrator_statistics_fetched'));
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $search = $request->get('q', '');

        $narrators = HadithNarrator::where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            })
            ->limit(10)
            ->get(['id', 'name_ar', 'name_en', 'reliability_grade']);

        return $this->successResponse($narrators, __('admin.narrator_fetched'));
    }
}
