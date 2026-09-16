<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin\Hadith;

use App\Domain\Hadith\Enums\Grade;
use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Support\ArabicNormalizer;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Hadith\StoreHadithRequest;
use App\Http\Requests\Admin\Hadith\UpdateHadithRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Named HadithRecordController, not HadithController: App\Http\Controllers\Site
 * already owns that name, and two controllers with the same short name in an
 * import list is how the wrong one ends up wired.
 */
class HadithRecordController extends Controller
{
    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    public function index(Request $request): View
    {
        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'collection' => $request->integer('collection') ?: null,
            'book' => $request->integer('book') ?: null,
            'chapter' => $request->integer('chapter') ?: null,
            'grade' => $request->query('grade'),
            'status' => $request->query('status'),
        ];

        $hadiths = Hadith::query()
            ->with([
                'collection:id,title_ar,title_en,slug',
                'book:id,book_number,title_ar,title_en',
                'chapter:id,chapter_number,title_ar,title_en',
            ])
            ->when($filters['collection'], fn ($q, $id) => $q->where('collection_id', $id))
            ->when($filters['book'], fn ($q, $id) => $q->where('book_id', $id))
            ->when($filters['chapter'], fn ($q, $id) => $q->where('chapter_id', $id))
            ->when($filters['grade'] === 'ungraded', fn ($q) => $q->whereNull('grade'))
            ->when(
                $filters['grade'] && $filters['grade'] !== 'ungraded',
                fn ($q) => $q->where('grade', $filters['grade'])
            )
            ->when($filters['status'] === 'published', fn ($q) => $q->where('is_published', true))
            ->when($filters['status'] === 'draft', fn ($q) => $q->where('is_published', false))
            ->when($filters['q'] !== '', function ($query) use ($filters) {
                // Search the normalised index columns, with the term put through
                // the same normaliser the importer used — otherwise a term typed
                // with harakat never matches text stored without them.
                $term = $this->normalizer->normalize($filters['q']);

                return $query->where(function ($q) use ($term, $filters) {
                    $q->where('search_ar', 'LIKE', "%{$term}%")
                        ->orWhere('search_en', 'LIKE', '%'.mb_strtolower($filters['q']).'%')
                        ->orWhere('reference_key', 'LIKE', "%{$filters['q']}%")
                        ->orWhere('number', 'LIKE', "%{$filters['q']}%");
                });
            })
            ->orderBy('collection_id')->orderBy('sort_order')->orderBy('id')
            ->paginate(25)
            ->withQueryString();

        return view('admin.hadith.hadiths.index', [
            'hadiths' => $hadiths,
            'collections' => HadithCollection::orderBy('sort_order')->get(['id', 'title_ar', 'title_en']),
            'books' => $filters['collection']
                ? HadithBook::where('collection_id', $filters['collection'])
                    ->orderBy('sort_order')->get(['id', 'book_number', 'title_ar', 'title_en'])
                : collect(),
            'chapters' => $filters['book']
                ? HadithChapter::where('book_id', $filters['book'])
                    ->orderBy('sort_order')->get(['id', 'chapter_number', 'title_ar', 'title_en'])
                : collect(),
            'grades' => Grade::cases(),
            'filters' => $filters,
            'stats' => $this->stats(),
        ]);
    }

    public function show(Hadith $hadith): View
    {
        return view('admin.hadith.hadiths.show', [
            'hadith' => $hadith->load(['collection', 'book', 'chapter']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('admin.hadith.hadiths.create', $this->formData() + [
            'presetCollection' => $request->integer('collection') ?: null,
        ]);
    }

    public function store(StoreHadithRequest $request): RedirectResponse
    {
        $hadith = Hadith::create($this->withSearchColumns($request->validated()));

        return redirect()
            ->route('admin.hadiths.show', $hadith)
            ->with('success', __('hadith_admin.hadith_created'));
    }

    public function edit(Hadith $hadith): View
    {
        return view('admin.hadith.hadiths.edit', $this->formData() + ['hadith' => $hadith]);
    }

    public function update(UpdateHadithRequest $request, Hadith $hadith): RedirectResponse
    {
        $hadith->update($this->withSearchColumns($request->validated()));

        return redirect()
            ->route('admin.hadiths.show', $hadith)
            ->with('success', __('hadith_admin.hadith_updated'));
    }

    public function destroy(Hadith $hadith): RedirectResponse
    {
        // The chain FK is RESTRICT because it participates in a generated
        // uniqueness column. Remove only this narration's links atomically.
        DB::transaction(function () use ($hadith): void {
            $hadith->narrators()->delete();
            $hadith->delete();
        });

        return redirect()
            ->route('admin.hadiths.index')
            ->with('success', __('hadith_admin.hadith_deleted'));
    }

    public function toggleStatus(Hadith $hadith): RedirectResponse
    {
        $hadith->update(['is_published' => ! $hadith->is_published]);

        return back()->with('success', __('hadith_admin.status_updated'));
    }

    /**
     * `search_ar` / `search_en` are the columns public search actually reads;
     * `text_ar` / `text_en` are only displayed. The importer derives one from the
     * other, so an edit that changed the text without rebuilding the index left
     * the hadith findable by its OLD wording and not its new one — silently, with
     * the page still rendering correctly.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function withSearchColumns(array $data): array
    {
        if (array_key_exists('text_ar', $data)) {
            $data['search_ar'] = $this->normalizer->normalize($data['text_ar']);
        }

        if (array_key_exists('text_en', $data)) {
            $data['search_en'] = $this->normalizer->normalize($data['text_en']);
        }

        return $data;
    }

    /** @return array<string, mixed> */
    private function formData(): array
    {
        return [
            'collections' => HadithCollection::orderBy('sort_order')->get(['id', 'title_ar', 'title_en']),
            'books' => HadithBook::orderBy('collection_id')->orderBy('sort_order')
                ->get(['id', 'collection_id', 'book_number', 'title_ar', 'title_en']),
            'chapters' => HadithChapter::orderBy('book_id')->orderBy('sort_order')
                ->get(['id', 'book_id', 'chapter_number', 'title_ar', 'title_en']),
            'grades' => Grade::cases(),
        ];
    }

    /** @return array<string, int> */
    private function stats(): array
    {
        return [
            'total' => Hadith::count(),
            'published' => Hadith::where('is_published', true)->count(),
            'ungraded' => Hadith::whereNull('grade')->count(),
            'visible' => Hadith::publiclyVisible()->count(),
        ];
    }
}
