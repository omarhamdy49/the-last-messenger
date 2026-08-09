<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\TafsirResourceService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\StoreTafsirResourceRequest;
use App\Http\Requests\Admin\Quran\UpdateTafsirResourceRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin CRUD for tafsir sources (resources). A tafsir's verse text is imported
 * via `php artisan quran:import-tafsir`; this manages the source metadata that
 * drives the reader's tafsir-source selector.
 */
class TafsirResourceController extends Controller
{
    /**
     * Sources per page in the admin list.
     */
    private const PER_PAGE = 20;

    public function __construct(
        private readonly TafsirResourceService $resources,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(): View
    {
        $resources = $this->resources->paginate(self::PER_PAGE);

        return view('admin.quran.tafsir-resources.index', compact('resources'));
    }

    public function create(): View
    {
        return view('admin.quran.tafsir-resources.create');
    }

    public function store(StoreTafsirResourceRequest $request): RedirectResponse
    {
        $resource = $this->resources->create($request->toAttributes());

        $this->logger->logCreate($resource, $request->user());

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_saved'));
    }

    public function edit(int $id): View
    {
        $resource = $this->resources->findOrFail($id);

        return view('admin.quran.tafsir-resources.edit', compact('resource'));
    }

    public function update(UpdateTafsirResourceRequest $request, int $id): RedirectResponse
    {
        $resource = $this->resources->findOrFail($id);
        $oldValues = $resource->toArray();

        $resource = $this->resources->update($resource, $request->toAttributes());

        $this->logger->logUpdate($resource, $oldValues, $resource->toArray(), $request->user());

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_saved'));
    }

    public function destroy(Request $request, int $id): RedirectResponse
    {
        // Tafsir rows keep their text but lose the resource link (nullOnDelete).
        $resource = $this->resources->findOrFail($id);

        $this->resources->delete($resource);

        $this->logger->logDelete($resource, $request->user());

        return redirect()->route('admin.tafsir-resources.index')
            ->with('success', __('quran.admin.tafsir_resource_deleted'));
    }
}
