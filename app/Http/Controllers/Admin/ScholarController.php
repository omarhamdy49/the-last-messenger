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
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Scholar Controller
 * Manages Islamic scholars (علماء) - uses HadithNarrator model for now
 * TODO: Create dedicated Scholar model in future iterations
 */
class ScholarController extends AdminController
{
    /**
     * Supported locales
     */
    protected array $locales;

    public function __construct()
    {
        parent::__construct();
        $this->locales = array_keys(config('app.supported_locales', ['ar', 'en']));
    }

    /**
     * Display a listing of scholars
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $filters = $this->getFilters($request, [
            'era',
            'country',
            'status',
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        // Using HadithNarrator model to represent scholars for now
        $query = HadithNarrator::query();

        // Apply filters
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

        $scholars = $query->paginate($pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($scholars, 'تم جلب العلماء بنجاح');
        }

        return view('admin.hadith.scholars.index', compact('scholars'));
    }

    /**
     * Show the form for creating a new scholar
     */
    public function create(): View
    {
        $this->authorizeAdmin('create scholars');

        $locales = $this->locales;

        return view('admin.hadith.scholars.create', compact('locales'));
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
                'slug' => $validated['slug'] ?? Str::slug($validated['name']['ar']),
                'name' => $validated['name'],
                'biography_json' => $validated['biography_json'] ?? null,
                'reliability_grade' => $validated['reliability_grade'] ?? 'thiqah',
                'era' => $validated['era'] ?? null,
                'country' => $validated['country'] ?? null,
                'birth_hijri' => $validated['birth_hijri'] ?? null,
                'death_hijri' => $validated['death_hijri'] ?? null,
                'status' => $validated['status'] ?? 'active',
            ]);

            DB::commit();

            $this->logActivity('created', 'Scholar', $scholar->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $scholar,
                    'تم إنشاء العالم بنجاح'
                );
            }

            return redirect()
                ->route('admin.scholars.index')
                ->with('success', 'تم إنشاء العالم بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل إنشاء العالم: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء العالم: ' . $e->getMessage()]);
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
                'تم جلب العالم بنجاح'
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

        $locales = $this->locales;

        return view('admin.hadith.scholars.edit', compact('scholar', 'locales'));
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
                'slug' => $validated['slug'] ?? $scholar->slug,
                'name' => $validated['name'],
                'biography_json' => $validated['biography_json'] ?? $scholar->biography_json,
                'era' => $validated['era'] ?? $scholar->era,
                'country' => $validated['country'] ?? $scholar->country,
                'birth_hijri' => $validated['birth_hijri'] ?? $scholar->birth_hijri,
                'death_hijri' => $validated['death_hijri'] ?? $scholar->death_hijri,
                'status' => $validated['status'] ?? $scholar->status,
            ]);

            DB::commit();

            $this->logActivity('updated', 'Scholar', $scholar->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $scholar,
                    'تم تحديث العالم بنجاح'
                );
            }

            return redirect()
                ->route('admin.scholars.index')
                ->with('success', 'تم تحديث العالم بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل تحديث العالم: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث العالم: ' . $e->getMessage()]);
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
                'name' => $scholar->name['ar'] ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'تم حذف العالم بنجاح');
            }

            return redirect()
                ->route('admin.scholars.index')
                ->with('success', 'تم حذف العالم بنجاح!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل حذف العالم: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'فشل حذف العالم: ' . $e->getMessage()]);
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

        return $this->successResponse($results, "تم حذف {$results['success']} عالم بنجاح");
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view scholars');

        $search = $request->get('q', '');

        $scholars = HadithNarrator::active()
            ->search($search)
            ->limit(10)
            ->get(['id', 'slug', 'name', 'era', 'country']);

        return $this->successResponse($scholars, 'تم جلب العلماء بنجاح');
    }
}
