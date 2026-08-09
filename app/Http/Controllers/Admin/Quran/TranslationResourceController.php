<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\TranslationResourceService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\StoreTranslationResourceRequest;
use App\Http\Requests\Admin\Quran\UpdateTranslationResourceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for translation editions (resources). A translation's verse text is
 * imported via `php artisan quran:import-translation`; this manages the edition
 * metadata that drives the reader's translation-edition selector. Exactly one
 * edition per language may be the default (used by the legacy single-translation
 * read path) — TranslationResourceService owns that rule.
 */
class TranslationResourceController extends Controller
{
    /**
     * Editions per page in the admin list.
     */
    private const PER_PAGE = 20;

    public function __construct(
        private readonly TranslationResourceService $resources,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(): View
    {
        $resources = $this->resources->paginate(self::PER_PAGE);

        return view('admin.quran.translation-resources.index', compact('resources'));
    }

    public function create(): View
    {
        return view('admin.quran.translation-resources.create');
    }

    public function store(StoreTranslationResourceRequest $request): RedirectResponse
    {
        $resource = $this->resources->create($request->toAttributes());

        $this->logger->logCreate($resource, $request->user());

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_saved'));
    }

    public function edit(int $id): View
    {
        $resource = $this->resources->findOrFail($id);

        return view('admin.quran.translation-resources.edit', compact('resource'));
    }

    public function update(UpdateTranslationResourceRequest $request, int $id): RedirectResponse
    {
        $resource = $this->resources->findOrFail($id);
        $oldValues = $resource->toArray();

        $resource = $this->resources->update($resource, $request->toAttributes());

        $this->logger->logUpdate($resource, $oldValues, $resource->toArray(), $request->user());

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_saved'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        // Translation rows keep their text but lose the resource link (nullOnDelete).
        $resource = $this->resources->findOrFail($id);

        $this->resources->delete($resource);

        $this->logger->logDelete($resource, $request->user());

        return redirect()->route('admin.translation-resources.index')
            ->with('success', __('quran.admin.translation_resource_deleted'));
    }
}
