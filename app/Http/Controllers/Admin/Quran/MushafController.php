<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Services\StructureService;
use App\Http\Controllers\Admin\Quran\Concerns\QueuesQuranCommands;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\UpdateMushafRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Mushaf layouts (page → line → word). Layouts sync from Quran Foundation; editors
 * choose which are offered on the site and which is the default.
 */
final class MushafController extends Controller
{
    use QueuesQuranCommands;

    public function __construct(private readonly StructureService $structure) {}

    public function index(): View
    {
        return view('admin.quran.mushafs.index', ['mushafs' => $this->structure->mushafs()]);
    }

    public function show(Request $request, int $mushaf): View
    {
        $model = $this->structure->mushaf($mushaf) ?? abort(404);
        $pageNumber = max(1, min($model->pages_count ?: 604, $request->integer('page', 1)));

        return view('admin.quran.mushafs.show', ['mushaf' => $model, 'pageNumber' => $pageNumber] + $this->structure->mushafPage($mushaf, $pageNumber));
    }

    public function update(UpdateMushafRequest $request, int $mushaf): RedirectResponse|JsonResponse
    {
        $this->structure->mushaf($mushaf) ?? abort(404);
        $updated = $this->structure->updateMushaf($mushaf, [
            'name' => (string) $request->validated('name'),
            'is_active' => $request->boolean('is_active'),
            'is_default' => $request->boolean('is_default'),
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $updated->id]])
            : back()->with('success', __('quran.manage.saved'));
    }

    public function fonts(Request $request, int $mushaf): RedirectResponse|JsonResponse
    {
        $model = $this->structure->mushaf($mushaf) ?? abort(404);
        if ($model->font_version === null) {
            return back()->with('error', __('quran.manage.no_font_version'));
        }

        return $this->queueCommand($request, 'quran:qf:download-assets', ['fonts' => $model->font_version]);
    }

    public function resync(Request $request, int $mushaf): RedirectResponse|JsonResponse
    {
        $this->structure->mushaf($mushaf) ?? abort(404);

        return $this->queueCommand($request, 'quran:qf:sync', ['resources' => 'mushafs:'.$mushaf]);
    }
}
