<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Hadith\Models\HadithCollection;
use App\Http\Requests\Admin\Hadith\StoreCollectionRequest;
use App\Http\Requests\Admin\Hadith\UpdateCollectionRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Collection Controller
 * Manages Hadith collections (e.g., Sahih collections, Sunan collections)
 */
class CollectionController extends AdminController
{
    /**
     * Display a listing of collections
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view collections');

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $query = HadithCollection::query();

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name_ar', 'LIKE', "%{$search}%")
                  ->orWhere('name_en', 'LIKE', "%{$search}%");
            });
        }

        // Apply sorting
        $query->orderBy($pagination['sort'], $pagination['order']);

        $collections = $query->with('books')->paginate($pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($collections, 'تم جلب المجموعات بنجاح');
        }

        return view('admin.hadith.collections.index', compact('collections'));
    }

    /**
     * Show the form for creating a new collection
     */
    public function create(): View
    {
        $this->authorizeAdmin('create collections');

        return view('admin.hadith.collections.create');
    }

    /**
     * Store a newly created collection
     */
    public function store(StoreCollectionRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create collections');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Create collection
            $collection = HadithCollection::create([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? '',
                'slug_ar' => $validated['slug_ar'] ?? Str::slug($validated['name_ar']),
                'slug_en' => $validated['slug_en'] ?? '',
                'description_ar' => $validated['description_ar'] ?? null,
                'description_en' => $validated['description_en'] ?? null,
                'abbreviation' => $validated['abbreviation'] ?? null,
                'total_hadiths' => $validated['total_hadiths'] ?? 0,
            ]);

            DB::commit();

            $this->logActivity('created', 'HadithCollection', $collection->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $collection,
                    'تم إنشاء المجموعة بنجاح'
                );
            }

            return redirect()
                ->route('admin.hadith.collections.index')
                ->with('success', 'تم إنشاء المجموعة بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل إنشاء المجموعة: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء المجموعة: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified collection
     */
    public function show(HadithCollection $collection): View|JsonResponse
    {
        $this->authorizeAdmin('view collections');

        $collection->load(['books']);

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $collection,
                'تم جلب المجموعة بنجاح'
            );
        }

        return view('admin.hadith.collections.show', compact('collection'));
    }

    /**
     * Show the form for editing the collection
     */
    public function edit(HadithCollection $collection): View
    {
        $this->authorizeAdmin('edit collections');

        return view('admin.hadith.collections.edit', compact('collection'));
    }

    /**
     * Update the specified collection
     */
    public function update(UpdateCollectionRequest $request, HadithCollection $collection): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit collections');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Update collection
            $collection->update([
                'name_ar' => $validated['name_ar'],
                'name_en' => $validated['name_en'] ?? $collection->name_en,
                'slug_ar' => $validated['slug_ar'] ?? $collection->slug_ar,
                'slug_en' => $validated['slug_en'] ?? $collection->slug_en,
                'description_ar' => $validated['description_ar'] ?? $collection->description_ar,
                'description_en' => $validated['description_en'] ?? $collection->description_en,
                'abbreviation' => $validated['abbreviation'] ?? $collection->abbreviation,
                'total_hadiths' => $validated['total_hadiths'] ?? $collection->total_hadiths,
            ]);

            DB::commit();

            $this->logActivity('updated', 'HadithCollection', $collection->id, [
                'name' => $validated['name_ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $collection,
                    'تم تحديث المجموعة بنجاح'
                );
            }

            return redirect()
                ->route('admin.hadith.collections.index')
                ->with('success', 'تم تحديث المجموعة بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل تحديث المجموعة: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث المجموعة: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified collection
     */
    public function destroy(HadithCollection $collection): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete collections');

        try {
            // Check if collection has books
            if ($collection->books()->count() > 0) {
                if ($this->expectsJson(request())) {
                    return $this->errorResponse('لا يمكن حذف مجموعة تحتوي على كتب', 422);
                }

                return back()->withErrors(['error' => 'لا يمكن حذف مجموعة تحتوي على كتب']);
            }

            $collection->delete();

            $this->logActivity('deleted', 'HadithCollection', $collection->id, [
                'name' => $collection->name_ar ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'تم حذف المجموعة بنجاح');
            }

            return redirect()
                ->route('admin.hadith.collections.index')
                ->with('success', 'تم حذف المجموعة بنجاح!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل حذف المجموعة: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'فشل حذف المجموعة: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete collections
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete collections');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:hadith_collections,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $collection = HadithCollection::findOrFail($id);

            // Check if collection has books
            if ($collection->books()->count() > 0) {
                throw new \Exception('لا يمكن حذف مجموعة تحتوي على كتب');
            }

            $collection->delete();
        });

        return $this->successResponse($results, "تم حذف {$results['success']} مجموعة بنجاح");
    }

}
