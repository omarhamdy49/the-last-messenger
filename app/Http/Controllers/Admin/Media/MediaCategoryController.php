<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Services\MediaProgramService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaCategoryRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Admin Media Category Controller
 *
 * Categories mirror the ProgramCategory enum; the row carries editable
 * presentation (name/description/icon/color), visibility and order.
 */
class MediaCategoryController extends Controller
{
    public function __construct(
        protected MediaProgramService $programService,
    ) {}

    /**
     * GET /su/portal/v1/admin/media-categories
     */
    public function index(): View
    {
        $categories = $this->programService->getAllCategoriesWithProgramCounts();

        return view('admin.media-categories.index', compact('categories'));
    }

    /**
     * GET /su/portal/v1/admin/media-categories/{key}
     */
    public function show(Request $request, string $key): View
    {
        abort_unless(ProgramCategory::tryFrom($key) !== null, 404);

        $category = $this->programService->findCategoryByKey($key);

        if (! $category) {
            abort(404, 'Category not found');
        }

        $perPage = min(max($request->integer('per_page', 12), 1), 50);
        $programs = $this->programService->paginatePrograms($perPage, $key, 'created_at', 'desc');
        $programs->appends($request->query());

        return view('admin.media-categories.show', compact('category', 'programs'));
    }

    /**
     * PATCH /su/portal/v1/admin/media-categories/{key}
     * Handles both the visibility toggle and the edit form.
     */
    public function update(UpdateMediaCategoryRequest $request, string $key): RedirectResponse
    {
        abort_unless(ProgramCategory::tryFrom($key) !== null, 404);

        $validated = $request->validated();

        if (array_key_exists('name', $validated)) {
            $attributes = [
                'name' => array_filter($validated['name'] ?? [], fn ($value) => filled($value)) ?: null,
                'description' => array_filter($validated['description'] ?? [], fn ($value) => filled($value)) ?: null,
                'icon' => $validated['icon'] ?? null,
                'color' => $validated['color'] ?? null,
            ];
            if (isset($validated['display_order'])) {
                $attributes['display_order'] = (int) $validated['display_order'];
            }
            if (isset($validated['is_featured'])) {
                $attributes['is_featured'] = (bool) $validated['is_featured'];
            }

            $this->programService->updateCategory($key, $attributes);
        } else {
            $this->programService->setCategoryFeatured($key, $request->boolean('is_featured'));
        }

        $target = $request->input('redirect') === 'show'
            ? route('admin.media-categories.show', $key)
            : route('admin.media-categories.index');

        return redirect($target)->with('success', __('admin.updated_successfully'));
    }

    /**
     * DELETE /su/portal/v1/admin/media-categories/{key}
     * Refused while programs are still assigned to the key.
     */
    public function destroy(string $key): RedirectResponse
    {
        abort_unless(ProgramCategory::tryFrom($key) !== null, 404);

        $deleted = $this->programService->deleteCategory($key);

        if (! $deleted) {
            return redirect()
                ->route('admin.media-categories.index')
                ->with('error', __('admin.category_has_programs'));
        }

        return redirect()
            ->route('admin.media-categories.index')
            ->with('success', __('admin.category_deleted'));
    }

    /**
     * POST /su/portal/v1/admin/media-categories/reorder
     */
    public function reorder(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'keys' => ['required', 'array', 'min:1'],
            'keys.*' => ['string', Rule::in(array_column(ProgramCategory::cases(), 'value'))],
        ]);

        $this->programService->reorderCategories($validated['keys']);

        return response()->json(['success' => true]);
    }
}
