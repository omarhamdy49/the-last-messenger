<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Content;

use App\Domain\Content\Services\HomeSectionService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Content\ReorderHomeSectionsRequest;
use App\Http\Requests\Admin\Content\ToggleHomeSectionRequest;
use App\Http\Requests\Admin\Content\UpdateHomeSectionRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

final class HomeSectionController extends Controller
{
    public function __construct(
        private readonly HomeSectionService $service,
    ) {}

    public function index(): View
    {
        return view('admin.content.home-sections.index', [
            'sections' => $this->service->forAdmin(),
        ]);
    }

    public function edit(int $home_section): View
    {
        $section = $this->service->findForAdmin($home_section);
        abort_if($section === null, 404);

        return view('admin.content.home-sections.edit', compact('section'));
    }

    public function update(UpdateHomeSectionRequest $request, int $home_section): RedirectResponse
    {
        abort_unless($this->service->update($request->toData($home_section)), 404);

        return redirect()
            ->route('admin.content.home-sections.index')
            ->with('success', __('admin.updated_successfully'));
    }

    public function toggle(ToggleHomeSectionRequest $request, int $home_section): RedirectResponse
    {
        abort_unless($this->service->setActive($home_section, $request->boolean('is_active')), 404);

        return back()->with('success', __('admin.updated_successfully'));
    }

    public function reorder(ReorderHomeSectionsRequest $request): RedirectResponse
    {
        $this->service->reorder($request->toData());

        return back()->with('success', __('admin.order_saved'));
    }
}
