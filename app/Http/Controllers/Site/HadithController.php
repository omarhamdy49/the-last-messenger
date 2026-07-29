<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Hadith\Application\DTOs\BookDTO;
use App\Domain\Hadith\Application\DTOs\CollectionDTO;
use App\Domain\Hadith\Application\DTOs\HadithDetailDTO;
use App\Domain\Hadith\Application\DTOs\HadithDTO;
use App\Domain\Hadith\Services\HadithQueryService;
use App\Domain\Shared\Application\DTOs\PaginatedResult;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Site\Concerns\NormalizesHadithGrading;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Throwable;

final class HadithController extends Controller
{
    use NormalizesHadithGrading;

    /** Collections that may be filtered/listed in the explorer dropdown. */
    private const EXPLORER_MAX_COLLECTIONS = 60;

    /** Allowed result page sizes for the explorer (capped to protect the DB). */
    private const EXPLORER_PER_PAGE_OPTIONS = [10, 20, 30, 50];

    /** Allowed result page sizes for the chapter reader. */
    private const CHAPTER_PER_PAGE_OPTIONS = [10, 15, 20];

    /** Canonical grading filter values the chapter reader accepts. */
    private const CHAPTER_GRADING_FILTERS = ['sahih', 'hasan', 'daif'];

    /** Native language names (endonyms) for the chapter reader's translation toggle. */
    private const LANGUAGE_ENDONYMS = [
        'ar' => 'العربية',
        'en' => 'English',
        'fr' => 'Français',
        'tr' => 'Türkçe',
        'ur' => 'اردو',
    ];

    public function __construct(
        private readonly HadithQueryService $hadithQueryService,
    ) {}

    /**
     * Hadith books library index — list all collections as book-cover cards.
     * All collections server-rendered; Alpine handles client-side search + category filter.
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_hadith_index_{$locale}",
            3600,
            function () use ($locale): array {
                try {
                    // The service returns CollectionDTO objects in `items`.
                    $result = $this->hadithQueryService->listCollections(
                        perPage: 50,
                        page: 1,
                        onlyActive: false,
                        sortBy: 'name_ar',
                        sortDirection: 'asc',
                        locale: $locale,
                    );

                    $books = collect($result->items)
                        ->map(fn ($collection) => $this->mapCollection($collection, $locale))
                        ->values()
                        ->all();

                    return [
                        'books' => $books,
                        'categories' => $this->buildCategories($books),
                        'stats' => [
                            'collections' => count($books),
                            'hadiths' => array_sum(array_column($books, 'hadithCount')),
                            'books' => array_sum(array_column($books, 'bookCount')),
                        ],
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['books' => [], 'categories' => [], 'stats' => [
                        'collections' => 0, 'hadiths' => 0, 'books' => 0,
                    ]];
                }
            },
        );

        return view('public.hadith.index', $payload);
    }

    /**
     * Single collection reader — real Arabic collection name, counts, and a searchable
     * grid of its books/chapters that link into the hadith singles.
     *
     * Books are paginated (Bukhari et al. have dozens of books and 5k+ hadiths) so we
     * never dump the full hadith set here.
     */
    public function book(Request $request, string $slug): View
    {
        $locale = app()->getLocale();
        $page = max(1, (int) $request->query('page', 1));

        $cacheKey = "public_hadith_book_{$slug}_{$locale}_{$page}";

        // Cache only serializable data — the request-scoped paginator is built afterwards.
        $payload = Cache::remember($cacheKey, 3600, function () use ($slug, $locale, $page): ?array {
            try {
                $collection = $this->hadithQueryService->getCollectionBySlug($slug, false, $locale);
            } catch (Throwable) {
                // Unknown slug — let the controller render a 404 below.
                return null;
            }

            try {
                $booksResult = $this->hadithQueryService->listBooksByCollection(
                    collectionId: $collection->id,
                    perPage: 24,
                    page: $page,
                    sortBy: 'book_number',
                    sortDirection: 'asc',
                    locale: $locale,
                );

                return [
                    'collection' => $this->mapCollection($collection, $locale),
                    'books' => collect($booksResult->items)
                        ->map(fn (BookDTO $book) => $this->mapBook($book, $locale))
                        ->values()
                        ->all(),
                    'pagination' => [
                        'total' => $booksResult->total,
                        'per_page' => $booksResult->perPage,
                        'current_page' => $booksResult->currentPage,
                    ],
                ];
            } catch (Throwable $e) {
                report($e);

                // Collection resolved but books failed — show the header with an empty grid.
                return [
                    'collection' => $this->mapCollection($collection, $locale),
                    'books' => [],
                    'pagination' => null,
                ];
            }
        });

        if ($payload === null) {
            abort(404);
        }

        $paginator = $payload['pagination'] !== null
            ? $this->buildPaginatorFromMeta($payload['pagination'], $request->url())
            : null;

        return view('public.hadith.book', [
            'collection' => $payload['collection'],
            'books' => $payload['books'],
            'paginator' => $paginator,
        ]);
    }

    /**
     * Chapter (kitab) reader — paginated hadiths of one book within a collection,
     * with search-within-chapter, grading filter pills, per-page control, and
     * previous/next chapter navigation by book number.
     *
     * The schema links hadiths to a required chapter, and each chapter belongs
     * to the book addressed by its number within the collection.
     */
    public function chapter(Request $request, string $slug, int $chapter): View
    {
        $locale = app()->getLocale();

        $search = trim((string) $request->query('q', ''));
        $grading = (string) $request->query('grading', '');
        if (! in_array($grading, self::CHAPTER_GRADING_FILTERS, true)) {
            $grading = '';
        }
        $perPage = (int) $request->query('per_page', self::CHAPTER_PER_PAGE_OPTIONS[1]);
        if (! in_array($perPage, self::CHAPTER_PER_PAGE_OPTIONS, true)) {
            $perPage = self::CHAPTER_PER_PAGE_OPTIONS[1];
        }
        $page = max(1, (int) $request->query('page', 1));

        try {
            $context = $this->hadithQueryService->getChapterContext($slug, $chapter, $locale);
        } catch (Throwable) {
            abort(404);
        }

        $hadiths = [];
        $paginator = null;
        $total = 0;

        try {
            $result = $this->hadithQueryService->listChapterHadiths(
                bookId: $context->book->id,
                perPage: $perPage,
                page: $page,
                gradingStatus: $grading !== '' ? $grading : null,
                search: $search,
                locale: $locale,
            );

            $total = $result->total;
            $hadiths = collect($result->items)
                ->map(fn (HadithDTO $hadith) => $this->mapHadithCard($hadith, $locale))
                ->values()
                ->all();

            $paginator = $this->buildPaginator($result, $request->url(), $request->query());
        } catch (Throwable $e) {
            report($e);
        }

        // The "translation" is the opposite of the active reading language. Decide
        // which hadiths on this page actually have one — a cheap existence check
        // (no text loaded); the text itself is fetched lazily on toggle.
        $altLang = $locale === 'ar' ? 'en' : 'ar';
        $translatableIds = [];
        try {
            $ids = array_column($hadiths, 'id');
            $translatableIds = $ids !== []
                ? $this->hadithQueryService->hadithIdsWithTranslation($ids, $altLang)
                : [];
        } catch (Throwable $e) {
            report($e);
        }

        return view('public.hadith.chapter', [
            'collection' => $this->mapCollection($context->collection, $locale),
            'chapter' => [
                'id' => $context->book->id,
                'number' => $context->book->bookNumber,
                'title' => $context->book->getTitle($locale) ?: ($context->book->chapterTitleAr ?? ''),
                'hadithCount' => $context->book->hadithCount,
            ],
            'navigation' => [
                'previous' => $context->previousNumber !== null
                    ? ['number' => $context->previousNumber, 'title' => $context->getPreviousTitle($locale)]
                    : null,
                'next' => $context->nextNumber !== null
                    ? ['number' => $context->nextNumber, 'title' => $context->getNextTitle($locale)]
                    : null,
            ],
            'filters' => ['q' => $search, 'grading' => $grading, 'per_page' => $perPage],
            'gradingOptions' => $this->explorerGradingOptions(),
            'perPageOptions' => self::CHAPTER_PER_PAGE_OPTIONS,
            'hadiths' => $hadiths,
            'paginator' => $paginator,
            'total' => $total,
            'altLang' => $altLang,
            'altLabel' => self::LANGUAGE_ENDONYMS[$altLang] ?? strtoupper($altLang),
            'altRtl' => in_array($altLang, ['ar', 'ur'], true),
            'translatableIds' => $translatableIds,
        ]);
    }

    /**
     * Lazy translation payload for the chapter reader: returns the opposite-language
     * translation text for the requested hadith ids, keyed by id. Loaded on demand
     * when the visitor opens the translation toggle so chapter pages never ship the
     * (often large) translation text up front.
     */
    public function translations(Request $request): \Illuminate\Http\JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'max:60'],
            'ids.*' => ['integer', 'min:1'],
            'lang' => ['required', 'string', \Illuminate\Validation\Rule::in(array_keys(self::LANGUAGE_ENDONYMS))],
        ]);

        $ids = array_slice(array_values(array_unique(array_map('intval', $validated['ids']))), 0, 60);

        try {
            $translations = $this->hadithQueryService->hadithTranslations($ids, $validated['lang']);
        } catch (Throwable $e) {
            report($e);
            $translations = [];
        }

        return response()->json(['translations' => $translations]);
    }

    /**
     * Single hadith reader — Amiri Arabic text, colour-coded grading, translation tabs
     * for languages that exist, isnad chain, scholarly commentaries, per-scholar
     * gradings, and optional significance / historical-context panels.
     */
    public function show(int $id): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember("public_hadith_show_{$id}_{$locale}", 3600, function () use ($id, $locale): ?array {
            try {
                $detail = $this->hadithQueryService->getHadithDetail($id, $locale);
            } catch (Throwable) {
                return null;
            }

            try {
                $related = $this->relatedHadiths($detail->hadith, $locale);
            } catch (Throwable $e) {
                report($e);
                $related = [];
            }

            return [
                'hadith' => $this->mapHadithDetail($detail, $locale),
                'related' => $related,
            ];
        });

        if ($payload === null) {
            abort(404);
        }

        return view('public.hadith.show', $payload);
    }

    /**
     * Parallel narrations — side-by-side comparison of every hadith sharing the
     * same normalized Arabic text (text_hash) across collections and books.
     */
    public function narrations(int $id): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember("public_hadith_narrations_{$id}_{$locale}", 3600, function () use ($id, $locale): ?array {
            try {
                $narrations = $this->hadithQueryService->getParallelNarrations($id, $locale);
            } catch (Throwable) {
                return null;
            }

            return [
                'base' => $this->mapNarrationVersion($narrations->base, $locale),
                'versions' => collect($narrations->parallels)
                    ->map(fn (HadithDetailDTO $version) => $this->mapNarrationVersion($version, $locale))
                    ->values()
                    ->all(),
            ];
        });

        if ($payload === null) {
            abort(404);
        }

        return view('public.hadith.narrations', $payload);
    }

    /**
     * Advanced search / explorer — full-text search with collection + grading filters,
     * sorting, per-page control, and server-rendered paginated result cards.
     */
    public function explorer(Request $request): View
    {
        $locale = app()->getLocale();

        $query = trim((string) $request->query('q', ''));
        $collectionId = (int) $request->query('collection', 0);
        $grading = (string) $request->query('grading', '');
        $sort = $request->query('sort') === 'desc' ? 'desc' : 'asc';
        $perPage = (int) $request->query('per_page', self::EXPLORER_PER_PAGE_OPTIONS[1]);
        $perPage = in_array($perPage, self::EXPLORER_PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::EXPLORER_PER_PAGE_OPTIONS[1];

        // Filter options (collections list) are stable — cache independently of the query.
        $collections = $this->explorerCollections($locale);
        $gradingOptions = $this->explorerGradingOptions();

        $hasFilters = $query !== '' || $collectionId > 0 || $grading !== '';

        $results = [];
        $paginator = null;
        $total = 0;

        if ($hasFilters) {
            try {
                $filters = [];
                if ($collectionId > 0) {
                    $filters['collection_id'] = $collectionId;
                }
                if ($grading !== '') {
                    $filters['grading_status'] = $grading;
                }

                $result = $this->hadithQueryService->searchHadiths(
                    searchQuery: $query,
                    perPage: $perPage,
                    filters: $filters,
                    locale: $locale,
                    page: max(1, (int) $request->query('page', 1)),
                    sortBy: 'hadith_number',
                    sortDirection: $sort,
                );

                $total = $result->total;
                $results = collect($result->items)
                    ->map(fn (HadithDTO $hadith) => $this->mapHadithCard($hadith, $locale))
                    ->values()
                    ->all();

                $paginator = $this->buildPaginator($result, $request->url(), $request->query());
            } catch (Throwable $e) {
                report($e);
                $results = [];
                $paginator = null;
                $total = 0;
            }
        }

        return view('public.hadith.explorer', [
            'collections' => $collections,
            'gradingOptions' => $gradingOptions,
            'perPageOptions' => self::EXPLORER_PER_PAGE_OPTIONS,
            'filters' => [
                'q' => $query,
                'collection' => $collectionId,
                'grading' => $grading,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'hasFilters' => $hasFilters,
            'results' => $results,
            'paginator' => $paginator,
            'total' => $total,
        ]);
    }

    /**
     * Related hadiths: other hadiths from the same book (excluding the current one).
     */
    private function relatedHadiths(HadithDTO $hadith, string $locale): array
    {
        $result = $this->hadithQueryService->listHadithsByBook(
            bookId: $hadith->bookId,
            perPage: 7,
            page: 1,
            gradingStatus: null,
            sortBy: 'hadith_number',
            sortDirection: 'asc',
            locale: $locale,
        );

        return collect($result->items)
            ->reject(fn (HadithDTO $item) => $item->id === $hadith->id)
            ->take(6)
            ->map(fn (HadithDTO $item) => $this->mapHadithCard($item, $locale))
            ->values()
            ->all();
    }

    /**
     * Collections available as explorer filter options.
     *
     * @return array<int, array{id: int, name: string}>
     */
    private function explorerCollections(string $locale): array
    {
        return Cache::remember("public_hadith_explorer_collections_{$locale}", 3600, function () use ($locale): array {
            try {
                $result = $this->hadithQueryService->listCollections(
                    perPage: self::EXPLORER_MAX_COLLECTIONS,
                    page: 1,
                    onlyActive: false,
                    sortBy: 'name_ar',
                    sortDirection: 'asc',
                    locale: $locale,
                );

                return collect($result->items)
                    ->map(fn (CollectionDTO $c) => ['id' => $c->id, 'name' => $c->getName($locale)])
                    ->values()
                    ->all();
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });
    }

    /**
     * Grading filter options — the curated canonical buckets the repository can filter on.
     * (grading_status is stored as free-text scholarly verdicts; only the canonical
     * machine values below map cleanly to a WHERE clause.)
     *
     * @return array<int, array{value: string, label: string, variant: string}>
     */
    private function explorerGradingOptions(): array
    {
        return [
            ['value' => 'sahih', 'label' => __('hadith.public.grading_sahih'), 'variant' => 'sahih'],
            ['value' => 'hasan', 'label' => __('hadith.public.grading_hasan'), 'variant' => 'hasan'],
            ['value' => 'daif', 'label' => __('hadith.public.grading_daif'), 'variant' => 'daif'],
        ];
    }

    /**
     * Map a CollectionDTO to the plain array the views consume.
     */
    private function mapCollection(CollectionDTO $collection, string $locale): array
    {
        return [
            'id' => $collection->id,
            'nameAr' => $collection->nameAr,
            'nameEn' => $collection->nameEn,
            'name' => $collection->getName($locale),
            'slug' => $collection->slug,
            'description' => $collection->getDescription($locale),
            'author' => $collection->authorName,
            'hadithCount' => $collection->hadithCount,
            'bookCount' => $collection->booksCount,
            'category' => $this->categoryFor($collection->nameAr),
            'featured' => $collection->isFeatured,
        ];
    }

    /**
     * Map a BookDTO (a book/chapter inside a collection) for the book grid.
     */
    private function mapBook(BookDTO $book, string $locale): array
    {
        $title = $book->getTitle($locale);
        if ($title === '') {
            $title = $book->chapterTitleAr ?? $book->chapterTitleEn ?? '';
        }

        return [
            'id' => $book->id,
            'number' => $book->bookNumber,
            'title' => $title,
            'titleEn' => $book->titleEn,
            'hadithCount' => $book->hadithCount,
            'chaptersCount' => $book->chaptersCount,
        ];
    }

    /**
     * Map a HadithDTO into the full single-view shape, including a normalized grading
     * descriptor and only the translation tabs that actually carry text.
     */
    private function mapHadith(HadithDTO $hadith, string $locale): array
    {
        $translations = $this->extractTranslations($hadith);
        $primaryText = $translations['ar']['text']
            ?? ($translations[$locale]['text'] ?? null)
            ?? (reset($translations)['text'] ?? null);

        return [
            'id' => $hadith->id,
            'number' => $hadith->hadithNumber,
            'collectionName' => $hadith->collectionName,
            'bookId' => $hadith->bookId,
            'bookTitle' => $hadith->bookTitle,
            'grading' => $this->gradingDescriptor($hadith->gradingStatus),
            'gradingAgreement' => $hadith->gradingAgreement,
            'arabicText' => $primaryText,
            'translations' => $translations,
            'themes' => $hadith->themes,
            'significance' => $hadith->significance,
            'historicalContext' => $hadith->historicalContext,
        ];
    }

    /**
     * Map a HadithDetailDTO into the single-view shape: the base hadith array
     * plus isnad narrators, commentaries grouped by author, scholar gradings,
     * and the parallel-narrations count.
     */
    private function mapHadithDetail(HadithDetailDTO $detail, string $locale): array
    {
        return array_merge($this->mapHadith($detail->hadith, $locale), [
            'narrators' => $this->mapIsnad($detail->isnad),
            'commentaryGroups' => $this->groupCommentaries($detail->commentaries),
            'scholarGradings' => $detail->scholarGradings,
            'parallelsCount' => $detail->parallelsCount,
        ]);
    }

    /**
     * Shape the isnad chain for the sidebar timeline + narrator modal.
     *
     * @param  array<int, array<string, mixed>>  $isnad
     * @return array<int, array<string, mixed>>
     */
    private function mapIsnad(array $isnad): array
    {
        $lastPosition = count($isnad);

        return collect($isnad)
            ->filter(fn (array $link) => ($link['name'] ?? null) !== null)
            ->values()
            ->map(fn (array $link, int $index) => [
                'id' => $link['id'] ?? $index,
                'name' => $link['name'],
                'kunya' => $link['kunya'],
                'reliability' => $link['reliability'],
                'deathYearHijri' => $link['deathYearHijri'],
                'bio' => $link['biography'],
                'method' => $link['transmissionMethod'],
                'position' => $link['position'],
                // First link in the chain (the companion) gets the highlighted style.
                'type' => $index === 0 ? 'companion' : ($index === $lastPosition - 1 ? 'compiler' : 'narrator'),
            ])
            ->all();
    }

    /**
     * Group flat commentary entries into per-author accordions.
     *
     * @param  array<int, array<string, mixed>>  $commentaries
     * @return array<int, array{author: string, items: array<int, array<string, mixed>>}>
     */
    private function groupCommentaries(array $commentaries): array
    {
        return collect($commentaries)
            ->filter(fn (array $commentary) => trim((string) ($commentary['text'] ?? '')) !== '')
            ->groupBy(fn (array $commentary) => $commentary['author'] ?: __('hadith.public.single_commentary_unknown_author'))
            ->map(fn ($items, string $author) => [
                'author' => $author,
                'items' => $items->values()->all(),
            ])
            ->values()
            ->all();
    }

    /**
     * Map one version (base or parallel) for the narrations comparison page.
     */
    private function mapNarrationVersion(HadithDetailDTO $version, string $locale): array
    {
        $card = $this->mapHadithCard($version->hadith, $locale);

        return array_merge($card, [
            'isnad' => $this->mapIsnad($version->isnad),
            'scholarGradings' => $version->scholarGradings,
        ]);
    }

    /**
     * Compact card shape used in explorer results and "related" lists.
     */
    private function mapHadithCard(HadithDTO $hadith, string $locale): array
    {
        $translations = $this->extractTranslations($hadith);
        $text = $translations['ar']['text']
            ?? ($translations[$locale]['text'] ?? null)
            ?? (reset($translations)['text'] ?? null);

        // Locale-aware primary text: the matn in the active locale, falling back to
        // Arabic. The opposite-language translation is fetched lazily by the chapter
        // reader (see HadithController::translations) so it never loads up front.
        $primaryLang = (isset($translations[$locale]) && trim((string) ($translations[$locale]['text'] ?? '')) !== '')
            ? $locale
            : 'ar';
        $primaryText = $translations[$primaryLang]['text'] ?? $text;

        return [
            'id' => $hadith->id,
            'number' => $hadith->hadithNumber,
            'collectionName' => $hadith->collectionName,
            'bookTitle' => $hadith->bookTitle,
            'grading' => $this->gradingDescriptor($hadith->gradingStatus),
            'text' => $text,
            'primaryText' => $primaryText,
            'primaryLang' => $primaryLang,
        ];
    }

    /**
     * Keep only translations that actually carry text, and normalize each entry to
     * ['text' => ..., 'translation' => ...].
     *
     * @return array<string, array{text: ?string, translation: ?string}>
     */
    private function extractTranslations(HadithDTO $hadith): array
    {
        $out = [];
        foreach ($hadith->translations as $code => $entry) {
            $text = is_array($entry) ? ($entry['text'] ?? null) : (is_string($entry) ? $entry : null);
            $translation = is_array($entry) ? ($entry['translation'] ?? null) : null;

            if (($text === null || $text === '') && ($translation === null || $translation === '')) {
                continue;
            }

            $out[$code] = ['text' => $text ?: null, 'translation' => $translation ?: null];
        }

        return $out;
    }

    /**
     * Build a LengthAwarePaginator from a PaginatedResult so the shared pagination
     * component (which expects a paginator) can render links and a jump-to-page UI.
     *
     * @param  PaginatedResult<mixed>  $result
     * @param  array<string, mixed>  $queryParams
     */
    private function buildPaginator(PaginatedResult $result, string $baseUrl, array $queryParams = []): LengthAwarePaginator
    {
        $paginator = new LengthAwarePaginator(
            items: $result->items,
            total: $result->total,
            perPage: max(1, $result->perPage),
            currentPage: $result->currentPage,
            options: ['path' => $baseUrl],
        );

        if ($queryParams !== []) {
            unset($queryParams['page']);
            $paginator->appends($queryParams);
        }

        return $paginator;
    }

    /**
     * Build a paginator from cached page metadata (no items needed — only links/labels
     * are rendered). Used by the book page, whose payload is cached without the
     * request-scoped paginator.
     *
     * @param  array{total: int, per_page: int, current_page: int}  $meta
     */
    private function buildPaginatorFromMeta(array $meta, string $baseUrl): LengthAwarePaginator
    {
        return new LengthAwarePaginator(
            items: [],
            total: $meta['total'],
            perPage: max(1, $meta['per_page']),
            currentPage: $meta['current_page'],
            options: ['path' => $baseUrl],
        );
    }

    /**
     * Derive a browsing category from the collection's Arabic name.
     */
    private function categoryFor(string $nameAr): string
    {
        return match (true) {
            str_contains($nameAr, 'صحيح') => 'sahih',
            str_contains($nameAr, 'سنن') => 'sunan',
            str_contains($nameAr, 'مسند') => 'masanid',
            str_contains($nameAr, 'الأربعون') || str_contains($nameAr, 'أربعون') => 'arbaeen',
            default => 'other',
        };
    }

    /**
     * Build category filter pills with live counts from the mapped books.
     *
     * @param  array<int, array<string, mixed>>  $books
     * @return array<int, array<string, mixed>>
     */
    private function buildCategories(array $books): array
    {
        $definitions = [
            'all' => 'library',
            'sahih' => 'shield-check',
            'sunan' => 'book-open',
            'masanid' => 'users',
            'arbaeen' => 'list-checks',
            'other' => 'bookmark',
        ];

        $categories = [];
        foreach ($definitions as $id => $icon) {
            $count = $id === 'all'
                ? count($books)
                : count(array_filter($books, fn ($b) => $b['category'] === $id));

            if ($id !== 'all' && $count === 0) {
                continue;
            }

            $categories[] = [
                'id' => $id,
                'label' => __("hadith.public.category_{$id}"),
                'icon' => $icon,
                'count' => $count,
            ];
        }

        return $categories;
    }
}
