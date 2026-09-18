<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Services\AudioService;
use App\Domain\Quran\Services\ChapterService;
use App\Domain\Quran\Services\ResourceService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\AudioFileRequest;
use App\Http\Requests\Admin\Quran\FillAudioTemplateRequest;
use App\Http\Requests\Admin\Quran\UpdateTimestampsRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Whole-chapter audio files of one chapter reciter, and the per-verse timings the
 * player uses to highlight and seek.
 */
final class ChapterAudioController extends Controller
{
    public function __construct(
        private readonly ResourceService $resources,
        private readonly AudioService $audio,
        private readonly ChapterService $chapters,
    ) {}

    public function index(int $resource): View
    {
        $reciter = $this->reciter($resource);

        return view('admin.quran.audio.chapter-index', [
            'resource' => $reciter,
            'files' => $this->audio->chapterFiles($reciter)->keyBy('chapter_id'),
            'chapters' => $this->chapters->all(false),
        ]);
    }

    public function store(AudioFileRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $reciter = $this->reciter($resource);
        $chapter = (int) $request->validated('chapter_id');

        return $this->save($request, $reciter, $chapter);
    }

    public function update(AudioFileRequest $request, int $resource, int $file): RedirectResponse|JsonResponse
    {
        $reciter = $this->reciter($resource);
        $existing = $this->audio->findChapterFile($reciter, $file) ?? abort(404);

        return $this->save($request, $reciter, (int) $existing->chapter_id);
    }

    public function destroy(Request $request, int $resource, int $file): RedirectResponse|JsonResponse
    {
        $reciter = $this->reciter($resource);
        $this->audio->findChapterFile($reciter, $file) ?? abort(404);
        $this->audio->deleteChapterFile($reciter, $file);

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', __('quran.manage.deleted'));
    }

    public function fill(FillAudioTemplateRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $reciter = $this->reciter($resource);

        try {
            $report = $this->audio->fillChaptersFromTemplate($reciter, (string) $request->validated('url_template'));
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => $report->toArray()])
            : back()->with('success', __('quran.manage.fill_summary', ['written' => $report->written, 'updated' => $report->updated, 'unchanged' => $report->unchanged]));
    }

    public function timestamps(Request $request, int $resource, int $file): View|JsonResponse
    {
        $reciter = $this->reciter($resource);
        $model = $this->audio->findChapterFile($reciter, $file) ?? abort(404);
        $timings = $this->audio->timings($reciter->id, (int) $model->chapter_id);

        // The audio manager's editor loads a surah's timings on demand.
        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => $timings]);
        }

        return view('admin.quran.audio.timestamps', [
            'resource' => $reciter,
            'file' => $model,
            'chapter' => $this->chapters->find((int) $model->chapter_id),
            'json' => json_encode($timings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'timings' => $timings,
        ]);
    }

    public function updateTimestamps(UpdateTimestampsRequest $request, int $resource, int $file): RedirectResponse|JsonResponse
    {
        $reciter = $this->reciter($resource);
        $model = $this->audio->findChapterFile($reciter, $file) ?? abort(404);
        $report = $this->audio->replaceTimestamps($model, $request->rows());

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => $report->toArray()])
            : back()->with('success', __('quran.manage.timestamps_saved', ['count' => $report->expected]));
    }

    private function save(AudioFileRequest $request, QuranResource $reciter, int $chapter): RedirectResponse|JsonResponse
    {
        try {
            $upload = $request->file('file');
            $url = $upload !== null ? $this->audio->storeUpload($upload, $reciter, sprintf('%03d', $chapter)) : (string) $request->validated('url');
            $row = $this->audio->saveChapterFile($reciter, $request->toData($chapter, $url, $upload !== null));
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $row->id, 'url' => $row->url]], 201)
            : back()->with('success', __('quran.manage.saved'));
    }

    private function reciter(int $id): QuranResource
    {
        return $this->resources->findOfKind(ResourceKind::CHAPTER_RECITER, $id) ?? abort(404);
    }
}
