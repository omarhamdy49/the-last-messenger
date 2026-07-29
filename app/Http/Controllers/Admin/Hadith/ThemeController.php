<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Repositories\ThemeRepositoryInterface;
use App\Http\Controllers\Admin\AdminController;
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
 * Manages Hadith themes/topics using Tag model (type = 'hadith')
 */
class ThemeController extends AdminController
{
    /**
     * Supported locales
     */
    protected array $locales;

    public function __construct(
        private readonly ThemeRepositoryInterface $themeRepository
    ) {
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

        $themes = $this->themeRepository->paginateWithFilters(
            perPage: $pagination['per_page'],
            search: $search,
            sortBy: $pagination['sort'],
            filters: ['sort_direction' => $pagination['order']],
        );

        if ($this->expectsJson($request)) {
            return $this->paginatedResponse($themes, __('admin.theme_fetched'));
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

            $theme = $this->themeRepository->create([
                'slug' => $validated['slug'] ?? Str::slug($validated['name']['ar']),
                'name' => $validated['name'],
            ]);

            DB::commit();

            $this->logActivity('created', 'Theme', $theme->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->createdResponse($theme, __('admin.theme_created'));
            }

            return redirect()
                ->route('admin.hadith.themes.index')
                ->with('success', __('admin.theme_created'));

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.theme_create_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.theme_create_failed')]);
        }
    }

    /**
     * Display the specified theme
     */
    public function show(int $id): View|JsonResponse
    {
        $this->authorizeAdmin('view themes');

        $theme = $this->themeRepository->findOrFail($id);
        $theme->loadCount('hadiths');

        if ($this->expectsJson(request())) {
            return $this->successResponse($theme, __('admin.theme_fetched'));
        }

        return view('admin.hadith.themes.show', compact('theme'));
    }

    /**
     * Show the form for editing the theme
     */
    public function edit(int $id): View
    {
        $this->authorizeAdmin('edit themes');

        $theme = $this->themeRepository->findOrFail($id);
        $locales = $this->locales;

        return view('admin.hadith.themes.edit', compact('theme', 'locales'));
    }

    /**
     * Update the specified theme
     */
    public function update(UpdateThemeRequest $request, int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('edit themes');

        DB::beginTransaction();
        try {
            $validated = $request->validated();

            $theme = $this->themeRepository->findOrFail($id);

            $this->themeRepository->update($id, [
                'slug' => $validated['slug'] ?? $theme->slug,
                'name' => $validated['name'],
            ]);

            $theme = $this->themeRepository->findOrFail($id);

            DB::commit();

            $this->logActivity('updated', 'Theme', $theme->id, [
                'name' => $validated['name']['ar'],
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse($theme, __('admin.theme_updated'));
            }

            return redirect()
                ->route('admin.hadith.themes.index')
                ->with('success', __('admin.theme_updated'));

        } catch (\Throwable $e) {
            DB::rollBack();

            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.theme_update_failed'));
            }

            return back()
                ->withInput()
                ->withErrors(['error' => __('admin.theme_update_failed')]);
        }
    }

    /**
     * Remove the specified theme
     */
    public function destroy(int $id): RedirectResponse|JsonResponse
    {
        $this->authorizeAdmin('delete themes');

        try {
            $theme = $this->themeRepository->findOrFail($id);

            if ($theme->hadiths()->count() > 0) {
                if ($this->expectsJson(request())) {
                    return $this->errorResponse(__('admin.theme_has_content'), 422);
                }

                return back()->withErrors(['error' => __('admin.theme_has_content')]);
            }

            $this->themeRepository->delete($id);

            $this->logActivity('deleted', 'Theme', $theme->id, [
                'name' => $theme->name['ar'] ?? 'N/A',
            ]);

            if ($this->expectsJson(request())) {
                return $this->successResponse(null, __('admin.theme_deleted'));
            }

            return redirect()
                ->route('admin.hadith.themes.index')
                ->with('success', __('admin.theme_deleted'));

        } catch (\Throwable $e) {
            if ($this->expectsJson(request())) {
                return $this->serverErrorResponse(__('admin.theme_delete_failed'));
            }

            return back()
                ->withErrors(['error' => __('admin.theme_delete_failed')]);
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
            'ids.*' => 'exists:hadith_themes,id',
        ]);

        $results = $this->handleBulkOperation($request, 'delete', function ($id) {
            $theme = $this->themeRepository->findOrFail((int) $id);

            if ($theme->hadiths()->count() > 0) {
                throw new \Exception(__('admin.theme_has_content'));
            }

            $this->themeRepository->delete((int) $id);
        });

        return $this->successResponse(
            $results,
            __('admin.theme_bulk_deleted', ['count' => $results['success']])
        );
    }

    /**
     * Search for autocomplete
     */
    public function search(Request $request): JsonResponse
    {
        $this->authorizeAdmin('view themes');

        $search = $request->get('q', '');

        $themes = $this->themeRepository->search($search, 10);

        return $this->successResponse($themes, __('admin.theme_fetched'));
    }
}
