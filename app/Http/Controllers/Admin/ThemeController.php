<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Domain\Blog\Models\Tag;
use App\Http\Requests\Admin\Hadith\StoreThemeRequest;
use App\Http\Requests\Admin\Hadith\UpdateThemeRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Theme Controller
 * Manages Hadith themes/topics using Tag model
 */
class ThemeController extends AdminController
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
     * Display a listing of themes
     */
    public function index(Request $request): View|JsonResponse
    {
        $this->authorizeAdmin('view themes');

        $search = $this->getSearchQuery($request);
        $pagination = $this->getPaginationParams($request);

        $query = Tag::where('type', 'hadith'); // Filter for hadith-related tags/themes

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

        $themes = $query->withCount('articles')->paginate($pagination['per_page']);

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($themes, 'تم جلب المواضيع بنجاح');
        }

        return view('admin.hadith.themes.index', compact('themes'));
    }

    /**
     * Show the form for creating a new theme
     */
    public function create(): View
    {
        $this->authorizeAdmin('create themes');

        $locales = $this->locales;

        return view('admin.hadith.themes.create', compact('locales'));
    }

    /**
     * Store a newly created theme
     */
    public function store(StoreThemeRequest $request): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('create themes');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Create theme using Tag model
            $theme = Tag::create([
                'slug' => $validated['slug'] ?? Str::slug($validated['name']['ar']),
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'type' => 'hadith',
                'color' => $validated['color'] ?? '#0056b3',
                'icon' => $validated['icon'] ?? 'tag',
            ]);

            DB::commit();

            $this->logActivity('created', 'Theme', $theme->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse(
                    $theme,
                    'تم إنشاء الموضوع بنجاح'
                );
            }

            return redirect()
                ->route('admin.themes.index')
                ->with('success', 'تم إنشاء الموضوع بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل إنشاء الموضوع: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل إنشاء الموضوع: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified theme
     */
    public function show(Tag $theme): View|JsonResponse
    {
        $this->authorizeAdmin('view themes');

        $theme->loadCount('articles');

        if ($this->expectsJson(request())) {
            return $this->successResponse(
                $theme,
                'تم جلب الموضوع بنجاح'
            );
        }

        return view('admin.hadith.themes.show', compact('theme'));
    }

    /**
     * Show the form for editing the theme
     */
    public function edit(Tag $theme): View
    {
        $this->authorizeAdmin('edit themes');

        $locales = $this->locales;

        return view('admin.hadith.themes.edit', compact('theme', 'locales'));
    }

    /**
     * Update the specified theme
     */
    public function update(UpdateThemeRequest $request, Tag $theme): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit themes');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            // Update theme
            $theme->update([
                'slug' => $validated['slug'] ?? $theme->slug,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? $theme->description,
                'color' => $validated['color'] ?? $theme->color,
                'icon' => $validated['icon'] ?? $theme->icon,
            ]);

            DB::commit();

            $this->logActivity('updated', 'Theme', $theme->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(
                    $theme,
                    'تم تحديث الموضوع بنجاح'
                );
            }

            return redirect()
                ->route('admin.themes.index')
                ->with('success', 'تم تحديث الموضوع بنجاح!');

        } catch (\Exception $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل تحديث الموضوع: ' . $e->getMessage());
            }

            return back()
                ->withInput()
                ->withErrors(['error' => 'فشل تحديث الموضوع: ' . $e->getMessage()]);
        }
    }

    /**
     * Remove the specified theme
     */
    public function destroy(Tag $theme): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete themes');

        try {
            // Check if theme is used
            if ($theme->articles()->count() > 0) {
                if ($this->expectsJson(request())) {
                    return $this->errorResponse('لا يمكن حذف موضوع مرتبط بمحتوى', 422);
                }

                return back()->withErrors(['error' => 'لا يمكن حذف موضوع مرتبط بمحتوى']);
            }

            $theme->delete();

            $this->logActivity('deleted', 'Theme', $theme->id, [
                'name' => $theme->name['ar'] ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, 'تم حذف الموضوع بنجاح');
            }

            return redirect()
                ->route('admin.themes.index')
                ->with('success', 'تم حذف الموضوع بنجاح!');

        } catch (\Exception $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse('فشل حذف الموضوع: ' . $e->getMessage());
            }

            return back()
                ->withErrors(['error' => 'فشل حذف الموضوع: ' . $e->getMessage()]);
        }
    }

    /**
     * Bulk delete themes
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        $this->authorizeAdmin('delete themes');

        $request->validate([
            'ids' => 'required|array',
            'ids.*' => 'exists:tags,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $theme = Tag::findOrFail($id);

            // Check if theme is used
            if ($theme->articles()->count() > 0) {
                throw new \Exception('لا يمكن حذف موضوع مرتبط بمحتوى');
            }

            $theme->delete();
        });

        return $this->successResponse($results, "تم حذف {$results['success']} موضوع بنجاح");
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view themes');

        $search = $request->get('q', '');

        $themes = Tag::where('type', 'hadith')
            ->where(function ($q) use ($search) {
                foreach ($this->locales as $locale) {
                    $q->orWhereRaw("JSON_EXTRACT(name, '$.{$locale}') LIKE ?", ["%{$search}%"]);
                }
            })
            ->limit(10)
            ->get(['id', 'slug', 'name', 'color']);

        return $this->successResponse($themes, 'تم جلب المواضيع بنجاح');
    }
}
