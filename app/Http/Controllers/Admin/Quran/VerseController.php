<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\VerseEditData;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Services\ResourceService;
use App\Domain\Quran\Services\VerseService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\BulkLockVersesRequest;
use App\Http\Requests\Admin\Quran\UpdateVerseRequest;
use App\Http\Requests\Admin\Quran\UpdateWordGlossesRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Verses and their words. The corpus is imported, so there is no create or delete;
 * editing any text locks the verse so a re-import does not overwrite the correction.
 */
final class VerseController extends Controller
{
    public function __construct(
        private readonly VerseService $verses,
        private readonly ResourceService $resources,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'chapter' => $request->filled('chapter') ? $request->integer('chapter') : null,
            'juz' => $request->filled('juz') ? $request->integer('juz') : null,
            'page' => $request->filled('mushaf_page') ? $request->integer('mushaf_page') : null,
            'sajdah' => $request->boolean('sajdah') ?: null,
            'locked' => $request->filled('locked') ? $request->boolean('locked') : null,
            'q' => $request->string('q')->toString() ?: null,
        ];

        return view('admin.quran.verses.index', $this->verses->adminIndex($filters, $request->integer('per_page', 50)));
    }

    public function show(int $verse): View
    {
        return view('admin.quran.verses.show', ['verse' => $this->verses->findForAdmin($verse) ?? abort(404)]);
    }

    public function words(int $verse): View
    {
        return view('admin.quran.verses.words', [
            'verse' => $this->verses->findForAdmin($verse) ?? abort(404),
            'glossResources' => $this->resources->active(ResourceKind::WORD_TRANSLATION)->concat($this->resources->active(ResourceKind::WORD_TRANSLITERATION)),
        ]);
    }

    public function edit(int $verse): View
    {
        return view('admin.quran.verses.edit', [
            'verse' => $this->verses->findForAdmin($verse) ?? abort(404),
            'scriptFields' => UpdateVerseRequest::SCRIPT_FIELDS,
        ]);
    }

    public function update(UpdateVerseRequest $request, int $verse): RedirectResponse|JsonResponse
    {
        $this->verses->findForAdmin($verse) ?? abort(404);
        $updated = $this->verses->update($verse, VerseEditData::fromValidated($request->validated()));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => ['id' => $updated->id, 'is_locked' => $updated->is_locked]]);
        }

        return redirect()->route('admin.quran.verses.show', $verse)->with('success', __('quran.manage.saved'));
    }

    public function lock(Request $request, int $verse): RedirectResponse|JsonResponse
    {
        $current = $this->verses->findForAdmin($verse) ?? abort(404);
        $this->verses->setLocked([$verse], ! $current->is_locked);

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['is_locked' => ! $current->is_locked]])
            : back()->with('success', __('quran.manage.saved'));
    }

    public function bulkLock(BulkLockVersesRequest $request): RedirectResponse|JsonResponse
    {
        $count = $this->verses->setLocked(array_map('intval', $request->validated('ids')), $request->boolean('locked'));

        return $request->expectsJson()
            ? response()->json(['success' => true, 'data' => ['updated' => $count]])
            : back()->with('success', __('quran.manage.bulk_updated', ['count' => $count]));
    }

    public function updateWord(UpdateWordGlossesRequest $request, int $word): RedirectResponse|JsonResponse
    {
        $glosses = [];
        foreach ((array) $request->validated('glosses') as $resourceId => $text) {
            $glosses[(int) $resourceId] = $text === null ? null : (string) $text;
        }
        $this->verses->updateWordGlosses($word, $glosses);

        return $request->expectsJson()
            ? response()->json(['success' => true])
            : back()->with('success', __('quran.manage.saved'));
    }
}
