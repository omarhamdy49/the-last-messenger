<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Enums\NarratorGrade;
use App\Domain\Hadith\Models\HadithNarrator;
use App\Domain\Hadith\Repositories\HadithNarratorRepositoryInterface;
use App\Http\Requests\Admin\Hadith\StoreNarratorRequest;
use App\Http\Requests\Admin\Hadith\UpdateNarratorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Narrator Controller
 * Manages Hadith narrators/transmitters with multi-language biographies
 */
class NarratorController extends AdminController
{
    /**
     * Supported locales
     */
    protected array $locales;

    public function __construct(
        protected HadithNarratorRepositoryInterface $repository
    ) {
        parent::__construct();
        $this->locales = array_keys(config('app.supported_locales', ['ar', 'en']));
    }

    /**
     * Display a listing of narrators
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $filters = $this->getFilters($request, [
            'reliability_grade',
            'era',
            'country',
            'status',
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $query = HadithNarrator::query();

        // Apply filters
        if (isset($filters['reliability_grade'])) {
            $query->where('reliability_grade', $filters['reliability_grade']);
        }

        if (isset($filters['era'])) {
            $query->where('era', $filters['era']);
        }

        if (isset($filters['country'])) {
            $query->where('country', $filters['country']);
        }

        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Apply search
        if ($search) {
            $query->search($search);
        }

        // Apply sorting
        $query->orderBy($pagination['sort'], $pagination['order']);

        $narrators = $query->withCount('hadiths')->paginate($pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($narrators, 'تم جلب الرواة بنجاح');
        }

        $grades = NarratorGrade::cases();
        $eras = ['صحابي', 'تابعي', 'تابع التابعين', 'القرن الثاني', 'القرن الثالث', 'القرن الرابع', 'القرن الخامس'];

        return view('admin.hadith.narrators.index', compact('narrators', 'grades', 'eras'));
    }

    /**
     * Show the form for creating a new narrator
     */
    public function create(): View
    {
        $this->authorizeAdmin('create narrators');

        $grades = NarratorGrade::cases();
        $locales = $this->locales;
        $narrators = HadithNarrator::active()->orderBy('name->ar')->get(); // For teachers/students

        return view('admin.hadith.narrators.create', compact('grades', 'locales', 'narrators'));
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
                'slug' => $validated['slug'] ?? Str::slug($validated['name']['ar']),
                'name' => $validated['name'],
                'biography_json' => $validated['biography_json'] ?? null,
                'reliability_grade' => NarratorGrade::from($validated['reliability_grade']),
                'era' => $validated['era'] ?? null,
                'country' => $validated['country'] ?? null,
                'birth_hijri' => $validated['birth_hijri'] ?? null,
                'death_hijri' => $validated['death_hijri'] ?? null,
                'teachers' => $validated['teachers'] ?? null,
                'students' => $validated['students'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            DB::commit();

            $this->logActivity('created', 'HadithNarrator', $narrator->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $narrator,
                    'تم إنشاء الراوي بنجاح'
                );
            }

            return redirect()
                ->route('admin.narrators.index')
                ->with('success', 'تم إنشاء الراوي بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل إنشاء الراوي: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء الراوي: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified narrator
     */
    public function show(HadithNarrator $narrator): View|JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $narrator->loadCount('hadiths');

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $narrator,
                'تم جلب الراوي بنجاح'
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

        $grades = NarratorGrade::cases();
        $locales = $this->locales;
        $narrators = HadithNarrator::active()
            ->where('id', '!=', $narrator->id)
            ->orderBy('name->ar')
            ->get();

        return view('admin.hadith.narrators.edit', compact('narrator', 'grades', 'locales', 'narrators'));
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
                'slug' => $validated['slug'] ?? $narrator->slug,
                'name' => $validated['name'],
                'biography_json' => $validated['biography_json'] ?? $narrator->biography_json,
                'reliability_grade' => NarratorGrade::from($validated['reliability_grade']),
                'era' => $validated['era'] ?? $narrator->era,
                'country' => $validated['country'] ?? $narrator->country,
                'birth_hijri' => $validated['birth_hijri'] ?? $narrator->birth_hijri,
                'death_hijri' => $validated['death_hijri'] ?? $narrator->death_hijri,
                'teachers' => $validated['teachers'] ?? $narrator->teachers,
                'students' => $validated['students'] ?? $narrator->students,
                'status' => $validated['status'] ?? $narrator->status,
            ]);

            DB::commit();

            $this->logActivity('updated', 'HadithNarrator', $narrator->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $narrator,
                    'تم تحديث الراوي بنجاح'
                );
            }

            return redirect()
                ->route('admin.narrators.index')
                ->with('success', 'تم تحديث الراوي بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل تحديث الراوي: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث الراوي: ' . $e->getMessage()]);
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
                    return $this->errorResponse('لا يمكن حذف راوي مرتبط بأحاديث', 422);
                }

                return back()->withErrors(['error' => 'لا يمكن حذف راوي مرتبط بأحاديث']);
            }

            $narrator->delete();

            $this->logActivity('deleted', 'HadithNarrator', $narrator->id, [
                'name' => $narrator->name['ar'] ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'تم حذف الراوي بنجاح');
            }

            return redirect()
                ->route('admin.narrators.index')
                ->with('success', 'تم حذف الراوي بنجاح!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل حذف الراوي: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'فشل حذف الراوي: ' . $e->getMessage()]);
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
                    'تم استعادة الراوي بنجاح'
                );
            }

            return redirect()
                ->route('admin.narrators.index')
                ->with('success', 'تم استعادة الراوي بنجاح!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل استعادة الراوي: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'فشل استعادة الراوي: ' . $e->getMessage()]);
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
                throw new \Exception('لا يمكن حذف راوي مرتبط بأحاديث');
            }

            $narrator->delete();
        });

        return $this->successResponse($results, "تم حذف {$results['success']} راوي بنجاح");
    }

    /**
     * Toggle narrator status
     */
    public function toggleStatus(HadithNarrator $narrator): JsonResponse
    {
        $this->authorizeAdmin('edit narrators');

        $newStatus = $narrator->status === 'active' ? 'inactive' : 'active';
        $narrator->update(['status' => $newStatus]);

        return $this->successResponse([
            'status' => $narrator->status
        ], 'تم تحديث حالة الراوي بنجاح');
    }

    /**
     * Get narrator statistics
     */
    public function statistics(HadithNarrator $narrator): JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $stats = [
            'hadith_count' => $narrator->hadiths()->count(),
            'sahih_count' => $narrator->hadiths()->sahih()->count(),
            'reliable_count' => $narrator->hadiths()->reliable()->count(),
            'teachers_count' => is_array($narrator->teachers) ? count($narrator->teachers) : 0,
            'students_count' => is_array($narrator->students) ? count($narrator->students) : 0,
        ];

        return $this->successResponse($stats, 'تم جلب إحصائيات الراوي بنجاح');
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view narrators');

        $search = $request->get('q', '');

        $narrators = HadithNarrator::active()
            ->search($search)
            ->limit(10)
            ->get(['id', 'slug', 'name', 'reliability_grade']);

        return $this->successResponse($narrators, 'تم جلب الرواة بنجاح');
    }
}
