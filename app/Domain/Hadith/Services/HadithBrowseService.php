<?php

declare(strict_types=1);

namespace App\Domain\Hadith\Services;

use App\Domain\Hadith\Models\Hadith;
use App\Domain\Hadith\Models\HadithBook;
use App\Domain\Hadith\Models\HadithChapter;
use App\Domain\Hadith\Models\HadithCollection;
use App\Domain\Shared\Support\ArabicNormalizer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * Read side of the public Hadith surface.
 *
 * Every hadith query goes through `Hadith::publiclyVisible()`. A bare
 * `where('is_published', true)` is not equivalent: a streaming re-import hides
 * the *collection* while its hadith rows stay published, so the naive filter
 * serves rows that should be dark.
 */
final class HadithBrowseService
{
    /** Collections are static between imports, so the index is worth caching. */
    private const INDEX_TTL = 3600;

    public function __construct(private readonly ArabicNormalizer $normalizer) {}

    /** @return Collection<int, HadithCollection> */
    public function collections(): Collection
    {
        return cache()->remember('hadith:collections:index', self::INDEX_TTL, fn () => HadithCollection::query()
            ->published()
            ->withCount(['books' => fn ($q) => $q->where('is_published', true)])
            ->orderBy('sort_order')
            ->get());
    }

    public function findCollection(string $slug): ?HadithCollection
    {
        return HadithCollection::query()->published()->where('slug', $slug)->first();
    }

    /**
     * Books of a collection with their real, publicly-visible hadith counts.
     *
     * The count is a subquery rather than `withCount('hadiths')` because the
     * latter counts hidden rows too, and a book advertising 300 hadiths that
     * opens on 40 is worse than no count at all.
     *
     * @return Collection<int, HadithBook>
     */
    public function books(HadithCollection $collection): Collection
    {
        return HadithBook::query()
            ->where('collection_id', $collection->id)
            ->where('is_published', true)
            ->select(['id', 'collection_id', 'book_number', 'title_ar', 'title_en', 'sort_order', 'first_hadith_number', 'last_hadith_number'])
            ->addSelect(['hadiths_count' => Hadith::query()
                ->selectRaw('count(*)')
                ->whereColumn('hadiths.book_id', 'hadith_books.id')
                ->where('hadiths.is_published', true)])
            ->orderBy('sort_order')
            ->get();
    }

    public function findBook(HadithCollection $collection, string $bookNumber): ?HadithBook
    {
        return HadithBook::query()
            ->where('collection_id', $collection->id)
            ->where('is_published', true)
            ->where('book_number', $bookNumber)
            ->first();
    }

    /** @return Collection<int, HadithChapter> */
    public function chapters(HadithBook $book): Collection
    {
        return HadithChapter::query()
            ->where('book_id', $book->id)
            ->where('is_published', true)
            ->orderBy('sort_order')
            ->get();
    }

    /** Hadiths of a book, paginated. */
    public function bookHadiths(HadithBook $book, int $perPage = 20): LengthAwarePaginator
    {
        return Hadith::query()
            ->publiclyVisible()
            ->where('book_id', $book->id)
            ->with('chapter:id,chapter_number,title_ar,title_en')
            ->orderBy('sort_order')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function findHadith(HadithCollection $collection, string $referenceKey): ?Hadith
    {
        return Hadith::query()
            ->publiclyVisible()
            ->forReference($collection->id, $referenceKey)
            ->with(['collection:id,slug,title_ar,title_en,reference_template', 'book:id,book_number,title_ar,title_en', 'chapter:id,chapter_number,title_ar,title_en'])
            ->first();
    }

    /**
     * Neighbours within the same collection, for the prev/next control.
     *
     * @return array{prev: ?Hadith, next: ?Hadith}
     */
    public function neighbours(Hadith $hadith): array
    {
        $base = fn () => Hadith::query()
            ->publiclyVisible()
            ->where('collection_id', $hadith->collection_id)
            ->select(['id', 'collection_id', 'reference_key', 'number', 'sort_order']);

        return [
            'prev' => $base()->where('sort_order', '<', $hadith->sort_order)->orderByDesc('sort_order')->first(),
            'next' => $base()->where('sort_order', '>', $hadith->sort_order)->orderBy('sort_order')->first(),
        ];
    }

    /**
     * Full-text search over the indexed `search_ar` / `search_en` columns.
     *
     * MATCH…AGAINST in boolean mode, not LIKE '%term%': the FULLTEXT indexes
     * exist (`ft_hadiths_search_ar`, `ft_hadiths_search_en`) and a leading
     * wildcard LIKE would table-scan 37k rows on every keystroke.
     *
     * @param  array{collection?: ?string, grade?: ?string}  $filters
     */
    public function search(string $term, array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $column = app()->getLocale() === 'ar' ? 'search_ar' : 'search_en';
        $boolean = $this->toBooleanMode($term);

        $query = Hadith::query()
            ->publiclyVisible()
            ->with(['collection:id,slug,title_ar,title_en', 'book:id,book_number,title_ar,title_en'])
            ->whereRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE)", [$boolean]);

        if (! empty($filters['collection'])) {
            $query->whereHas('collection', fn ($q) => $q->where('slug', $filters['collection']));
        }

        // '' is a real filter value: the importer stores NULL when the source
        // grading is ambiguous, and those are browsable as "غير مصنّف".
        if (isset($filters['grade']) && $filters['grade'] !== null && $filters['grade'] !== '') {
            $query->where('grade', $filters['grade']);
        }

        return $query
            ->orderByRaw("MATCH({$column}) AGAINST (? IN BOOLEAN MODE) DESC", [$boolean])
            ->paginate($perPage)
            ->withQueryString();
    }

    /**
     * Grade counts across the visible corpus, for the search facets.
     *
     * @return array<string, int>
     */
    public function gradeCounts(): array
    {
        return cache()->remember('hadith:grade:counts', self::INDEX_TTL, fn () => Hadith::query()
            ->publiclyVisible()
            ->selectRaw("COALESCE(grade, '') as g, count(*) as c")
            ->groupBy('g')
            ->pluck('c', 'g')
            ->all());
    }

    public function totalVisible(): int
    {
        return (int) cache()->remember('hadith:total', self::INDEX_TTL, fn () => Hadith::query()->publiclyVisible()->count());
    }

    /**
     * Fold the query the same way the indexed column was folded, then escape it
     * for BOOLEAN MODE and require every term.
     *
     * The normalizer pass is not optional. `search_ar` is stored folded — hamza
     * seats collapsed to bare alef, ta marbuta to ha, diacritics stripped — so a
     * reader typing "الصلاة" searches for a string that is not in the index
     * ("الصلاه" is), and gets zero results with no error. ArabicNormalizer's own
     * docblock calls this out as the failure it exists to prevent.
     *
     * Escaping matters separately: unescaped, a stray `+`, `-`, `*`, `(` or `"`
     * is an operator, so "الصلاة -" either errors or silently means the opposite
     * of what was typed.
     */
    private function toBooleanMode(string $term): string
    {
        $folded = $this->normalizer->normalize($term) ?? '';
        $words = preg_split('/\s+/u', trim($folded), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return collect($words)
            ->map(fn (string $w) => preg_replace('/[+\-><()~*"@]+/u', ' ', $w))
            ->map(fn (?string $w) => trim((string) $w))
            ->filter()
            ->map(fn (string $w) => '+'.$w.'*')
            ->implode(' ');
    }

    /** Invalidate the cached aggregates. Call after an import. */
    public static function forget(): void
    {
        foreach (['hadith:collections:index', 'hadith:grade:counts', 'hadith:total'] as $key) {
            cache()->forget($key);
        }
    }
}
