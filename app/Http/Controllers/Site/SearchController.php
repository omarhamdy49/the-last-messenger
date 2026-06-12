<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Shared\Services\SearchService;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Throwable;

final class SearchController extends Controller
{
    /**
     * Content modules surfaced on the public unified-search page.
     * Order here drives the order results are grouped/rendered in.
     *
     * @var array<int, string>
     */
    private const PUBLIC_MODULES = ['quran', 'hadith', 'article', 'seerah', 'sahaba'];

    private const PER_PAGE = 12;

    public function __construct(
        private readonly SearchService $searchService,
    ) {}

    /**
     * Unified search results page.
     *
     * Reads ?q= and optional ?type[]= filters, runs one cross-module search,
     * and hands the view server-rendered results plus per-type facet counts.
     * An empty query renders the discovery state (popular searches), never an error.
     */
    public function index(Request $request): View
    {
        $locale = app()->getLocale();
        $query = trim((string) $request->query('q', ''));
        $sort = $this->resolveSort((string) $request->query('sort', 'relevance'));

        // Type filter: ?type[]=hadith&type[]=quran — clamp to known public modules.
        $activeTypes = array_values(array_intersect(
            (array) $request->query('type', []),
            self::PUBLIC_MODULES,
        ));

        if ($query === '') {
            return view('public.search.index', [
                'query' => '',
                'sort' => $sort,
                'activeTypes' => [],
                'results' => [],
                'facets' => $this->emptyFacets(),
                'totalResults' => 0,
                'popularSearches' => $this->popularSearches(),
                'moduleMeta' => $this->moduleMeta(),
            ]);
        }

        $modulesToSearch = $activeTypes !== [] ? $activeTypes : self::PUBLIC_MODULES;

        $payload = $this->runSearch($query, $modulesToSearch, $sort, $locale);

        return view('public.search.index', [
            'query' => $query,
            'sort' => $sort,
            'activeTypes' => $activeTypes,
            'results' => $payload['results'],
            'facets' => $payload['facets'],
            'totalResults' => $payload['total'],
            'popularSearches' => $this->popularSearches(),
            'moduleMeta' => $this->moduleMeta(),
        ]);
    }

    /**
     * Execute the cross-module search and shape it for the view.
     *
     * @param  array<int, string>  $modules
     * @return array{results: array<int, array<string, mixed>>, facets: array<string, int>, total: int}
     */
    private function runSearch(string $query, array $modules, string $sort, string $locale): array
    {
        try {
            $raw = $this->searchService->search(
                query: $query,
                filters: [],
                options: [
                    'modules' => $modules,
                    'sort' => $sort,
                    'per_page' => self::PER_PAGE,
                ],
            );
        } catch (Throwable $e) {
            report($e);

            return ['results' => [], 'facets' => $this->emptyFacets(), 'total' => 0];
        }

        $facets = $this->emptyFacets();
        $results = [];

        foreach (($raw['results'] ?? []) as $module => $group) {
            $facets[$module] = (int) ($group['total'] ?? 0);

            foreach (($group['data'] ?? []) as $model) {
                $card = $this->mapResult($module, $model, $query, $locale);
                if ($card !== null) {
                    $results[] = $card;
                }
            }
        }

        // Modules excluded by an active type filter still need a real facet
        // count on their sidebar checkbox: one COUNT query per missing module
        // (none when no filter is active — counts derive from the results).
        foreach (array_diff(self::PUBLIC_MODULES, $modules) as $module) {
            try {
                $facets[$module] = $this->searchService->countWithin($module, $query);
            } catch (Throwable $e) {
                report($e);
            }
        }

        return [
            'results' => $results,
            'facets' => $facets,
            'total' => (int) ($raw['total_results'] ?? array_sum($facets)),
        ];
    }

    /**
     * Map a single module result model to a flat card array for the view.
     *
     * @return array<string, mixed>|null
     */
    private function mapResult(string $module, object $model, string $query, string $locale): ?array
    {
        return match ($module) {
            'quran' => $this->mapQuran($model, $locale),
            'hadith' => $this->mapHadith($model, $locale),
            'article' => $this->mapArticle($model, $locale),
            'seerah' => $this->mapSeerah($model, $locale),
            'sahaba' => $this->mapSahaba($model, $locale),
            default => null,
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function mapQuran(object $verse, string $locale): array
    {
        $text = $this->translationText($verse, ['ar', $locale], 'text');

        return [
            'type' => 'quran',
            'title' => __('search.verse_key', ['key' => $verse->verse_key ?? '']),
            'excerpt' => $text,
            'source' => __('search.source_quran'),
            'url' => $verse->surah_id
                ? route('public.quran.surah', $verse->surah_id)
                : route('public.quran'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapHadith(object $hadith, string $locale): array
    {
        $text = $this->translationText($hadith, ['ar', $locale], 'text', 'language_code');

        return [
            'type' => 'hadith',
            'title' => __('search.hadith_number', ['number' => $hadith->hadith_number ?? $hadith->id]),
            'excerpt' => $text,
            'source' => __('search.source_hadith'),
            'url' => route('public.hadith.show', $hadith->id),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapArticle(object $article, string $locale): array
    {
        $localeRow = $this->articleLocale($article, $locale);

        return [
            'type' => 'article',
            'title' => $localeRow?->title ?? __('search.untitled'),
            'excerpt' => $localeRow?->excerpt ?? $localeRow?->description ?? $this->plainExcerpt($localeRow?->content),
            'source' => __('search.source_article'),
            'url' => ($localeRow && $localeRow->slug)
                ? route('public.articles.show', $localeRow->slug)
                : route('public.articles'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSeerah(object $event, string $locale): array
    {
        $translation = $this->localeTranslation($event, $locale);

        return [
            'type' => 'seerah',
            'title' => $translation?->title ?? __('search.untitled'),
            'excerpt' => $translation?->description ?? $this->plainExcerpt($translation?->content ?? null),
            'source' => __('search.source_seerah'),
            'url' => ($event->slug)
                ? route('public.seerah.event', $event->slug)
                : route('public.seerah'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function mapSahaba(object $sahabi, string $locale): array
    {
        $translation = $this->localeTranslation($sahabi, $locale);

        return [
            'type' => 'sahaba',
            'title' => $translation?->name ?? $translation?->full_name ?? __('search.untitled'),
            'excerpt' => $translation?->biography ? $this->plainExcerpt($translation->biography) : ($translation?->kunya ?? ''),
            'source' => __('search.source_sahaba'),
            'url' => ($sahabi->slug)
                ? route('public.sahaba.show', $sahabi->slug)
                : route('public.sahaba'),
        ];
    }

    /**
     * Pull the best-available translation text from a loaded `translations` relation.
     *
     * @param  array<int, string>  $localePreference
     */
    private function translationText(
        object $model,
        array $localePreference,
        string $textColumn,
        string $localeColumn = 'lang',
    ): string {
        $translations = $model->relationLoaded('translations')
            ? $model->getRelation('translations')
            : collect();

        foreach ($localePreference as $code) {
            $match = $translations->firstWhere($localeColumn, $code);
            if ($match && filled($match->{$textColumn} ?? null)) {
                return (string) $match->{$textColumn};
            }
        }

        $first = $translations->first();

        return (string) ($first->{$textColumn} ?? '');
    }

    /**
     * Resolve a per-locale translation row (Sahaba/Seerah use a `locale` column).
     */
    private function localeTranslation(object $model, string $locale): ?object
    {
        $translations = $model->relationLoaded('translations')
            ? $model->getRelation('translations')
            : collect();

        return $translations->firstWhere('locale', $locale)
            ?? $translations->firstWhere('locale', 'ar')
            ?? $translations->first();
    }

    /**
     * Resolve an article's per-locale row from the loaded `locales` relation.
     */
    private function articleLocale(object $article, string $locale): ?object
    {
        $locales = $article->relationLoaded('locales')
            ? $article->getRelation('locales')
            : collect();

        return $locales->firstWhere('locale', $locale)
            ?? $locales->firstWhere('locale', 'ar')
            ?? $locales->first();
    }

    private function plainExcerpt(?string $html, int $length = 220): string
    {
        if ($html === null || $html === '') {
            return '';
        }

        return str(strip_tags($html))->squish()->limit($length)->toString();
    }

    private function resolveSort(string $sort): string
    {
        return in_array($sort, ['relevance', 'date', 'popular'], true) ? $sort : 'relevance';
    }

    /**
     * @return array<string, int>
     */
    private function emptyFacets(): array
    {
        return array_fill_keys(self::PUBLIC_MODULES, 0);
    }

    /**
     * View-side metadata (label key, icon, gradient) per public module.
     *
     * @return array<string, array<string, string>>
     */
    private function moduleMeta(): array
    {
        return [
            'quran' => ['label' => __('search.type_quran'),   'icon' => 'book-open',   'gradient' => 'from-[#0056b3] to-blue-600'],
            'hadith' => ['label' => __('search.type_hadith'),  'icon' => 'scroll-text', 'gradient' => 'from-[#218838] to-green-600'],
            'article' => ['label' => __('search.type_article'), 'icon' => 'file-text',   'gradient' => 'from-purple-500 to-pink-500'],
            'seerah' => ['label' => __('search.type_seerah'),  'icon' => 'map',         'gradient' => 'from-amber-500 to-orange-500'],
            'sahaba' => ['label' => __('search.type_sahaba'),  'icon' => 'users',       'gradient' => 'from-cyan-500 to-teal-500'],
        ];
    }

    /**
     * @return array<int, string>
     */
    private function popularSearches(): array
    {
        try {
            $popular = $this->searchService->getPopularSearches(null, 30)
                ->pluck('query')
                ->filter()
                ->unique()
                ->take(8)
                ->values()
                ->all();
        } catch (Throwable) {
            $popular = [];
        }

        if ($popular !== []) {
            return $popular;
        }

        // Curated fallback so the discovery state is never blank on a cold history.
        return ['الصلاة', 'الصيام', 'الزكاة', 'الصبر', 'الحج', 'التوحيد'];
    }
}
