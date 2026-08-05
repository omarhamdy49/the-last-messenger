<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Hadith\Models\HadithText;
use App\Domain\Hadith\Services\HadithAdminService;
use App\Domain\Shared\Services\AdminLoggerService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\UpdateHadithCollectionRequest;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class HadithCollectionController extends Controller
{
    public function __construct(
        private readonly HadithAdminService $service,
        private readonly AdminLoggerService $logger,
    ) {}

    public function index(Request $request): View
    {
        $search = trim($request->string('search')->toString());
        $status = $request->string('status')->toString();

        $collections = HadithCollection::query()
            ->withCount([
                'books as books_count' => fn (Builder $query) => $query->where('is_synthetic', false),
                'hadiths',
            ])
            ->when($search !== '', fn (Builder $query) => $query->where(
                fn (Builder $query) => $query->where('code', 'like', "%{$search}%")
                    ->orWhere('name', 'like', "%{$search}%"),
            ))
            ->when($status === 'published', fn (Builder $query) => $query->where('is_published', true))
            ->when($status === 'draft', fn (Builder $query) => $query->where('is_published', false))
            ->orderBy('display_order')
            ->get();

        $stats = [
            'total' => HadithCollection::query()->count(),
            'published' => HadithCollection::query()->where('is_published', true)->count(),
            'featured' => HadithCollection::query()->where('featured', true)->count(),
            'hadiths' => Hadith::query()->count(),
        ];

        return view('admin.hadith-collections.index', compact('collections', 'stats'));
    }

    public function show(HadithCollection $collection): View
    {
        $collection->loadCount([
            'books as books_count' => fn (Builder $query) => $query->where('is_synthetic', false),
            'hadiths',
        ]);

        $books = HadithBook::query()
            ->where('collection_id', $collection->id)
            ->where('is_synthetic', false)
            ->withCount(['chapters', 'hadiths'])
            ->orderBy('sort_order')
            ->get();

        $availableLanguages = HadithText::query()
            ->whereHas('hadith', fn (Builder $query) => $query->where('collection_id', $collection->id))
            ->distinct()
            ->count('locale');

        $previewLocales = array_values(array_unique([app()->getLocale(), 'ar', 'en']));

        $recentHadiths = Hadith::query()
            ->where('collection_id', $collection->id)
            ->with(['book:id,book_number,title', 'texts' => fn ($query) => $query->whereIn('locale', $previewLocales)])
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.hadith-collections.show', compact('collection', 'books', 'recentHadiths', 'availableLanguages'));
    }

    public function update(
        UpdateHadithCollectionRequest $request,
        HadithCollection $collection,
    ): RedirectResponse {
        $before = $collection->toArray();
        $updated = $this->service->updateCollection($collection, $request->validated());
        $this->logger->logUpdate($updated, $before, $updated->toArray(), $request->user('admin'));

        return redirect()
            ->route('admin.hadith-collections.show', $updated)
            ->with('success', __('hadith.admin.collection_updated'));
    }
}
