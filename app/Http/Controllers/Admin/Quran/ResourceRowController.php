<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\ContentRowData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\ChapterService;
use App\Domain\Quran\Services\ContentRowService;
use App\Domain\Quran\Services\ResourceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\ContentRowRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Rows of one translation, tafsir or chapter-info resource. Rows an editor writes
 * are locked by default so a Quran Foundation sync does not overwrite them.
 */
final class ResourceRowController extends Controller
{
    private const KINDS = [
        'translations' => ResourceKind::TRANSLATION,
        'tafsirs' => ResourceKind::TAFSIR,
        'chapter-infos' => ResourceKind::CHAPTER_INFO,
    ];

    public function __construct(
        private readonly ResourceService $resources,
        private readonly ContentRowService $rows,
        private readonly ChapterService $chapters,
    ) {}

    public function index(Request $request, int $resource): View
    {
        $rows = (string) $request->route('rows');
        $model = $this->resourceOf($resource, $rows);
        $chapter = $request->filled('chapter') ? max(1, min(114, $request->integer('chapter'))) : null;

        return view('admin.quran.resource-rows.index', [
            'resource' => $model,
            'rowsSegment' => $rows,
            'chapter' => $chapter,
            'chapters' => $this->chapters->all(false),
            'items' => $this->rows->paginate($model, $chapter, $request->integer('per_page', 20)),
        ]);
    }

    public function store(ContentRowRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $model = $this->resourceOf($resource, $request->rows());

        try {
            $row = $this->rows->save($model, ContentRowData::fromValidated($request->validated()));
        } catch (InvalidArgumentException $e) {
            return $this->fail($request, $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $row->getKey()]], 201)
            : back()->with('success', __('quran.manage.saved'));
    }

    public function update(ContentRowRequest $request, int $resource, int $row): RedirectResponse|JsonResponse
    {
        $model = $this->resourceOf($resource, $request->rows());
        $this->rows->find($model, $row) ?? abort(404);
        // The target (verse/chapter) never changes on update; the repository ignores it.
        $this->rows->update($model, $row, ContentRowData::fromValidated($request->validated()));

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', __('quran.manage.saved'));
    }

    public function destroy(Request $request, int $resource, int $row): RedirectResponse|JsonResponse
    {
        $model = $this->resourceOf($resource, (string) $request->route('rows'));
        $this->rows->find($model, $row) ?? abort(404);
        $this->rows->delete($model, $row);

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', __('quran.manage.deleted'));
    }

    public function import(Request $request, int $resource): RedirectResponse|JsonResponse
    {
        $model = $this->resourceOf($resource, (string) $request->route('rows'));
        $validated = $request->validate(['file' => ['required', 'file', 'max:20480', 'mimes:json,csv,txt']]);
        $file = $validated['file'];

        try {
            $report = $this->rows->importFile($model, (string) $file->getRealPath(), (string) $file->getClientOriginalExtension());
        } catch (InvalidArgumentException $e) {
            return $this->fail($request, $e->getMessage());
        }

        $summary = __('quran.manage.import_summary', [
            'written' => $report->written, 'updated' => $report->updated, 'unchanged' => $report->unchanged, 'failed' => count($report->failed),
        ]);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => $report->toArray()])
            : back()->with(count($report->failed) > 0 ? 'error' : 'success', $summary);
    }

    private function resourceOf(int $id, string $rows): QuranResource
    {
        $kind = self::KINDS[$rows] ?? abort(404);

        return $this->resources->findOfKind($kind, $id) ?? abort(404);
    }

    private function fail(Request $request, string $message): RedirectResponse|JsonResponse
    {
        return $request->expectsJson()
            ? response()->json(['success' => false, 'message' => $message], 422)
            : back()->withInput()->with('error', $message);
    }
}
