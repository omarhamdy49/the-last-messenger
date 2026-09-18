<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Quran;

use App\Domain\Quran\DTOs\ChapterData;
use App\Domain\Quran\Services\ChapterService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Quran\UpdateChapterRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * The 114 chapters. Imported from Quran Foundation, so there is no create or delete:
 * editors localize names, publish, link a location and set a cover.
 */
final class ChapterController extends Controller
{
    public function __construct(private readonly ChapterService $chapters) {}

    public function index(Request $request): View
    {
        $filters = [
            'search' => $request->string('search')->toString() ?: null,
            'revelation_place' => $request->string('revelation_place')->toString() ?: null,
            'published' => $request->string('published')->toString(),
        ];

        return view('admin.quran.chapters.index', $this->chapters->adminIndex($filters, $request->integer('per_page', 30)));
    }

    public function search(Request $request): JsonResponse
    {
        $chapters = $this->chapters->search($request->string('q')->toString(), $request->integer('limit', 20));

        return response()->json(['success' => true, 'data' => $chapters->map(fn ($c): array => [
            'id' => $c->id,
            'name' => $c->displayName(),
            'name_arabic' => $c->name_arabic,
            'verses_count' => $c->verses_count,
        ])->values()]);
    }

    public function show(int $chapter): View
    {
        return view('admin.quran.chapters.show', ['chapter' => $this->chapters->findForAdmin($chapter) ?? abort(404)]);
    }

    public function edit(int $chapter): View
    {
        return view('admin.quran.chapters.edit', ['chapter' => $this->chapters->findForAdmin($chapter) ?? abort(404)]);
    }

    public function update(UpdateChapterRequest $request, int $chapter): RedirectResponse|JsonResponse
    {
        $this->chapters->find($chapter) ?? abort(404);
        $cover = $request->file('cover')?->store('quran/chapters', 'public');
        $updated = $this->chapters->update($chapter, ChapterData::fromValidated($request->validated(), $cover ?: null));

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'data' => ['id' => $updated->id]]);
        }

        return redirect()->route('admin.quran.chapters.show', $chapter)->with('success', __('quran.manage.saved'));
    }
}
