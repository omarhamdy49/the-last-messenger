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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use InvalidArgumentException;

/**
 * Ayah-by-ayah audio files of one recitation: a URL (streamed from its source) or an
 * uploaded MP3 on the public disk, plus a URL-template filler for whole chapters.
 */
final class AudioFileController extends Controller
{
    public function __construct(
        private readonly ResourceService $resources,
        private readonly AudioService $audio,
        private readonly ChapterService $chapters,
    ) {}

    public function index(Request $request, int $resource): View
    {
        $recitation = $this->recitation($resource);
        $chapter = $request->filled('chapter') ? max(1, min(114, $request->integer('chapter'))) : null;

        return view('admin.quran.audio.ayah-index', [
            'resource' => $recitation,
            'chapter' => $chapter,
            'chapters' => $this->chapters->all(false),
            'files' => $this->audio->paginateAyahFiles($recitation, $chapter, $request->integer('per_page', 50)),
        ]);
    }

    public function store(AudioFileRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $recitation = $this->recitation($resource);
        $verseKey = (string) $request->validated('verse_key');

        return $this->save($request, $recitation, $verseKey, str_replace(':', '_', $verseKey));
    }

    public function update(AudioFileRequest $request, int $resource, int $file): RedirectResponse|JsonResponse
    {
        $recitation = $this->recitation($resource);
        $existing = $this->audio->findAyahFile($recitation, $file) ?? abort(404);
        $verseKey = (string) $existing->verse()->value('verse_key');

        return $this->save($request, $recitation, $verseKey, str_replace(':', '_', $verseKey));
    }

    public function destroy(Request $request, int $resource, int $file): RedirectResponse|JsonResponse
    {
        $recitation = $this->recitation($resource);
        $this->audio->findAyahFile($recitation, $file) ?? abort(404);
        $this->audio->deleteAyahFile($recitation, $file);

        return $request->expectsJson() ? response()->json(['success' => true]) : back()->with('success', __('quran.manage.deleted'));
    }

    public function fill(FillAudioTemplateRequest $request, int $resource): RedirectResponse|JsonResponse
    {
        $recitation = $this->recitation($resource);

        try {
            $report = $this->audio->fillAyahFromTemplate($recitation, (string) $request->validated('url_template'),
                $request->filled('chapter') ? $request->integer('chapter') : null);
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => $report->toArray()])
            : back()->with('success', __('quran.manage.fill_summary', ['written' => $report->written, 'updated' => $report->updated, 'unchanged' => $report->unchanged]));
    }

    private function save(AudioFileRequest $request, QuranResource $recitation, string $verseKey, string $fileName): RedirectResponse|JsonResponse
    {
        try {
            $upload = $request->file('file');
            $url = $upload !== null ? $this->audio->storeUpload($upload, $recitation, $fileName) : (string) $request->validated('url');
            $row = $this->audio->saveAyahFile($recitation, $request->toData($verseKey, $url, $upload !== null));
        } catch (InvalidArgumentException $e) {
            return $request->expectsJson()
                ? response()->json(['success' => false, 'message' => $e->getMessage()], 422)
                : back()->withInput()->with('error', $e->getMessage());
        }

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['id' => $row->id, 'url' => $row->url]], 201)
            : back()->with('success', __('quran.manage.saved'));
    }

    private function recitation(int $id): QuranResource
    {
        return $this->resources->findOfKind(ResourceKind::RECITATION, $id) ?? abort(404);
    }
}
