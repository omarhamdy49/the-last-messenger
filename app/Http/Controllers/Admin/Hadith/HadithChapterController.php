<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Services\HadithAdminService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\UpdateHadithChapterRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HadithChapterController extends Controller
{
    public function __construct(
        private readonly HadithAdminService $service,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $collectionId = $request->integer('collection_id');
        $bookId = $request->integer('book_id');
        $status = $request->string('status')->toString();

        $chapters = HadithChapter::query()
            ->with('book:id,collection_id,book_number,title', 'book.collection:id,code,name')
            ->withCount('hadiths')
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('chapter_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%"),
            ))
            ->when($collectionId > 0, fn (Builder $query) => $query->whereHas(
                'book',
                fn (Builder $book) => $book->where('collection_id', $collectionId),
            ))
            ->when($bookId > 0, fn (Builder $query) => $query->where('book_id', $bookId))
            ->when($status === 'published', fn (Builder $query) => $query->where('is_published', true))
            ->when($status === 'draft', fn (Builder $query) => $query->where('is_published', false))
            ->orderBy('book_id')->orderBy('sort_order')
            ->paginate(30)->withQueryString();

        $collections = HadithCollection::query()->orderBy('display_order')->get(['id', 'code', 'name']);
        $books = HadithBook::query()
            ->when($collectionId > 0, fn (Builder $query) => $query->where('collection_id', $collectionId))
            ->orderBy('collection_id')->orderBy('sort_order')
            ->get(['id', 'collection_id', 'book_number', 'title']);
        $stats = [
            'total' => HadithChapter::query()->count(),
            'published' => HadithChapter::query()->where('is_published', true)->count(),
            'books' => HadithBook::query()->count(),
            'hadiths' => Hadith::query()->whereNotNull('chapter_id')->count(),
        ];

        return view('admin.hadith-chapters.index', compact('chapters', 'collections', 'books', 'stats'));
    }

    public function edit(HadithChapter $chapter): View
    {
        $chapter->load('book.collection')->loadCount('hadiths');

        return view('admin.hadith-chapters.edit', compact('chapter'));
    }

    public function update(UpdateHadithChapterRequest $request, HadithChapter $chapter): RedirectResponse
    {
        $before = $chapter->toArray();
        $updated = $this->service->updateChapter($chapter, $request->validated());
        $this->logger->logUpdate($updated, $before, $updated->toArray(), $request->user('admin'));

        return redirect()->route('admin.hadith-chapters.edit', $updated)->with('success', __('hadith.admin.chapter_updated'));
    }
}
