<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Media;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Services\MediaProgramService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MediaProgram\UpdateMediaCategoryRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Admin Media Category Controller
 *
 * Categories mirror the ProgramCategory enum; admins toggle which ones
 * load (rows + filter pills) on the public programs list.
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
     * PATCH /su/portal/v1/admin/media-categories/{key}
     */
    public function update(UpdateMediaCategoryRequest $request, string $key): RedirectResponse
    {
        abort_unless(ProgramCategory::tryFrom($key) !== null, 404);

        $this->programService->setCategoryFeatured($key, $request->boolean('is_featured'));

        return redirect()
            ->route('admin.media-categories.index')
            ->with('success', __('admin.updated_successfully'));
    }
}
