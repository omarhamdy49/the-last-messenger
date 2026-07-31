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
use App\Http\Requests\Admin\Hadith\UpdateHadithBookRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HadithBookController extends Controller
{
    public function __construct(
        private readonly HadithAdminService $service,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $collectionId = $request->integer('collection_id');
        $status = $request->string('status')->toString();

        $books = HadithBook::query()
            ->with('collection:id,code,name')
            ->withCount(['chapters', 'hadiths'])
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('book_number', 'like', "%{$search}%")
                    ->orWhere('title', 'like', "%{$search}%"),
            ))
            ->when($collectionId > 0, fn (Builder $query) => $query->where('collection_id', $collectionId))
            ->when($status === 'published', fn (Builder $query) => $query->where('is_published', true))
            ->when($status === 'draft', fn (Builder $query) => $query->where('is_published', false))
            ->orderBy('collection_id')->orderBy('sort_order')
            ->paginate(30)->withQueryString();

        $collections = HadithCollection::query()->orderBy('display_order')->get(['id', 'code', 'name']);
        $stats = [
            'total' => HadithBook::query()->count(),
            'published' => HadithBook::query()->where('is_published', true)->count(),
            'chapters' => HadithChapter::query()->count(),
            'hadiths' => Hadith::query()->count(),
        ];

        return view('admin.hadith-books.index', compact('books', 'collections', 'stats'));
    }

    public function edit(HadithBook $book): View
    {
        $book->load('collection')->loadCount(['chapters', 'hadiths']);

        return view('admin.hadith-books.edit', compact('book'));
    }

    public function update(UpdateHadithBookRequest $request, HadithBook $book): RedirectResponse
    {
        $before = $book->toArray();
        $updated = $this->service->updateBook($book, $request->validated());
        $this->logger->logUpdate($updated, $before, $updated->toArray(), $request->user('admin'));

        return redirect()->route('admin.hadith-books.edit', $updated)->with('success', __('hadith.admin.book_updated'));
    }
}
