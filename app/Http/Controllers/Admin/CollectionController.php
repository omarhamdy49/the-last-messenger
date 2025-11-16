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
     * Supported locales
     */
    protected array $locales;

    public function __construct()
    {
        parent::__construct();
        $this->locales = array_keys(config('app.supported_locales', ['ar', 'en']));
    }

    /**
     * Display a listing of collections
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view collections');

        $filters = $this->getFilters($request, [
            'active',
            'featured',
        ]);

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $query = HadithCollection::query();

        // Apply filters
        if (isset($filters['active'])) {
            $query->where('active', filter_var($filters['active'], FILTER_VALIDATE_BOOLEAN));
        }

        if (isset($filters['featured'])) {
            $query->where('featured', filter_var($filters['featured'], FILTER_VALIDATE_BOOLEAN));
        }

        // Apply search
        if ($search) {
            $query->where(function ($q) use ($search) {
                foreach ($this->locales as $locale) {
                    $q->orWhereRaw("JSON_EXTRACT(name, '$.{$locale}') LIKE ?", ["%{$search}%"]);
                }
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

        $locales = $this->locales;

        return view('admin.hadith.collections.create', compact('locales'));
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
                'slug' => $validated['slug'] ?? Str::slug($validated['name']['ar']),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'author_name' => $validated['author_name'] ?? null,
                'active' => $request->boolean('active', true),
                'featured' => $request->boolean('featured', false),
                'display_order' => $validated['display_order'] ?? 0,
            ]);

            DB::commit();

            $this->logActivity('created', 'HadithCollection', $collection->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $collection,
                    'تم إنشاء المجموعة بنجاح'
                );
            }

            return redirect()
                ->route('admin.collections.index')
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

        $locales = $this->locales;

        return view('admin.hadith.collections.edit', compact('collection', 'locales'));
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
                'slug' => $validated['slug'] ?? $collection->slug,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $collection->description,
                'author_name' => $validated['author_name'] ?? $collection->author_name,
                'active' => $request->boolean('active', $collection->active),
                'featured' => $request->boolean('featured', $collection->featured),
                'display_order' => $validated['display_order'] ?? $collection->display_order,
            ]);

            DB::commit();

            $this->logActivity('updated', 'HadithCollection', $collection->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $collection,
                    'تم تحديث المجموعة بنجاح'
                );
            }

            return redirect()
                ->route('admin.collections.index')
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
                'name' => $collection->name['ar'] ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'تم حذف المجموعة بنجاح');
            }

            return redirect()
                ->route('admin.collections.index')
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
     * Toggle active status
     */
    public function toggleActive(HadithCollection $collection): JsonResponse
    {
        $this->authorizeAdmin('edit collections');

        $collection->update(['active' => !$collection->active]);

        return $this->successResponse([
            'active' => $collection->active
        ], 'تم تحديث حالة المجموعة بنجاح');
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(HadithCollection $collection): JsonResponse
    {
        $this->authorizeAdmin('edit collections');

        $collection->update(['featured' => !$collection->featured]);

        return $this->successResponse([
            'featured' => $collection->featured
        ], 'تم تحديث حالة التمييز بنجاح');
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

    /**
     * Reorder collections
     */
    public function reorder(Request $request): JsonResponse
    {
        $this->authorizeAdmin('edit collections');

        $request->validate([
            'order' => 'required|array',
            'order.*.id' => 'required|exists:hadith_collections,id',
            'order.*.position' => 'required|integer|min:0',
        ]);

        DB::beginTransaction();
        try {
            foreach ($request->input('order') as $item) {
                HadithCollection::where('id', $item['id'])
                    ->update(['display_order' => $item['position']]);
            }

            DB::commit();

            return $this->successResponse(null, 'تم تحديث ترتيب المجموعات بنجاح');

        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('فشل تحديث الترتيب: ' . $e->getMessage());
        }
    }
}
