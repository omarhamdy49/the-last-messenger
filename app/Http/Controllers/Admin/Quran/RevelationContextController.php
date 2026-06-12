<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Application\Commands\CreateRevelationContextCommand;
use App\Domain\Quran\Application\Commands\DeleteRevelationContextCommand;
use App\Domain\Quran\Application\Commands\UpdateRevelationContextCommand;
use App\Domain\Quran\Application\Handlers\CreateRevelationContextHandler;
use App\Domain\Quran\Application\Handlers\DeleteRevelationContextHandler;
use App\Domain\Quran\Application\Handlers\GetRevelationContextByIdHandler;
use App\Domain\Quran\Application\Handlers\ListRevelationContextsHandler;
use App\Domain\Quran\Application\Handlers\UpdateRevelationContextHandler;
use App\Domain\Quran\Application\Queries\GetRevelationContextByIdQuery;
use App\Domain\Quran\Application\Queries\ListRevelationContextsQuery;
use App\Domain\Quran\Models\Surah;
use App\Domain\Quran\Services\QuranCacheKeys;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRevelationContextRequest;
use App\Http\Requests\Admin\UpdateRevelationContextRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Admin Revelation Context (Asbab al-Nuzul) Controller
 */
class RevelationContextController extends Controller
{
    public function __construct(
        protected ListRevelationContextsHandler $listHandler,
        protected GetRevelationContextByIdHandler $getHandler,
        protected CreateRevelationContextHandler $createHandler,
        protected UpdateRevelationContextHandler $updateHandler,
        protected DeleteRevelationContextHandler $deleteHandler,
    ) {}

    /**
     * Display a listing of revelation contexts
     *
     * GET /su/portal/v1/admin/revelation-contexts
     */
    public function index(Request $request): View
    {
        $perPage = min(max($request->integer('per_page', 15), 1), 100);

        $contexts = $this->listHandler->handle(new ListRevelationContextsQuery(
            period: $request->input('period'),
            surahId: $request->integer('surah_id') ?: null,
            search: $request->input('search'),
            sort: $request->input('sort', 'mushaf'),
            perPage: $perPage,
            activeOnly: false,
            locale: 'ar',
        ));

        return view('admin.quran.revelation-contexts.index', [
            'contexts' => $contexts,
            'surahs' => $this->getSurahsForSelector(),
        ]);
    }

    /**
     * Show the form for creating a new revelation context
     *
     * GET /su/portal/v1/admin/revelation-contexts/create
     */
    public function create(): View
    {
        return view('admin.quran.revelation-contexts.create', [
            'surahs' => $this->getSurahsForSelector(),
        ]);
    }

    /**
     * Store a newly created revelation context
     *
     * POST /su/portal/v1/admin/revelation-contexts
     */
    public function store(StoreRevelationContextRequest $request): RedirectResponse
    {
        try {
            $context = $this->createHandler->handle(
                CreateRevelationContextCommand::fromValidated($request->validated())
            );

            return redirect()->route('admin.revelation-contexts.show', $context->id)
                ->with('success', __('quran.asbab.created'));
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('quran.asbab.create_failed')]);
        }
    }

    /**
     * Display the specified revelation context
     *
     * GET /su/portal/v1/admin/revelation-contexts/{id}
     */
    public function show(int $id): View
    {
        $context = $this->getHandler->handle(new GetRevelationContextByIdQuery($id));

        if ($context === null) {
            abort(404);
        }

        return view('admin.quran.revelation-contexts.show', ['context' => $context]);
    }

    /**
     * Show the form for editing the specified revelation context
     *
     * GET /su/portal/v1/admin/revelation-contexts/{id}/edit
     */
    public function edit(int $id): View
    {
        $context = $this->getHandler->handle(new GetRevelationContextByIdQuery($id));

        if ($context === null) {
            abort(404);
        }

        return view('admin.quran.revelation-contexts.edit', [
            'context' => $context,
            'surahs' => $this->getSurahsForSelector(),
        ]);
    }

    /**
     * Update the specified revelation context
     *
     * PUT/PATCH /su/portal/v1/admin/revelation-contexts/{id}
     */
    public function update(UpdateRevelationContextRequest $request, int $id): RedirectResponse
    {
        try {
            $context = $this->updateHandler->handle(
                UpdateRevelationContextCommand::fromValidated($id, $request->validated())
            );

            if ($context === null) {
                abort(404);
            }

            return redirect()->route('admin.revelation-contexts.show', $context->id)
                ->with('success', __('quran.asbab.updated'));
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return redirect()->back()
                ->withInput()
                ->withErrors(['error' => __('quran.asbab.update_failed')]);
        }
    }

    /**
     * Remove the specified revelation context
     *
     * DELETE /su/portal/v1/admin/revelation-contexts/{id}
     */
    public function destroy(int $id): RedirectResponse
    {
        $deleted = $this->deleteHandler->handle(new DeleteRevelationContextCommand($id));

        if (! $deleted) {
            abort(404);
        }

        return redirect()->route('admin.revelation-contexts.index')
            ->with('success', __('quran.asbab.deleted'));
    }

    /**
     * Helper: cached surah list for selector dropdowns (matches TafseerController).
     *
     * @return \Illuminate\Support\Collection
     */
    private function getSurahsForSelector()
    {
        return cache()->remember(QuranCacheKeys::surahSelector(), now()->addMinutes(30), function () {
            return Surah::select(['id', 'revelation_order', 'name_ar'])
                ->orderBy('revelation_order')
                ->get();
        });
    }
}
