<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Domain\Shared\Models\Language;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Repositories\ArticleRepository;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

final class ArticleController extends Controller
{
    public function __construct(
        private readonly ArticleRepository $articles,
    ) {}

    /**
     * Public articles library.
     *
     * Renders every published article as a JSON payload consumed by Alpine,
     * which handles search / category / tag / sort / view-mode / pagination
     * entirely client-side (the catalogue is small and fully cacheable).
     */
    public function index(): View
    {
        $locale = app()->getLocale();

        $payload = Cache::flexible(
            // flexible(), not remember(): rebuilding this reads every ready article plus
            // the category and tag facets. On expiry that ran once per concurrent request
            // (up to pm.max_children at a time) with no lock; now the first request past the
            // 30-min mark serves the stale payload and refreshes after the response.
            "public_articles_index_{$locale}",
            [1800, 3600],
            function () use ($locale): array {
                try {
                    // One column-scoped read of all ready articles (client paginates).
                    $articles = $this->articles->getPublishedForPublicIndex($locale)
                        ->map(fn (Article $article) => $this->mapCard($article, $locale))
                        ->filter()
                        ->values()
                        ->all();

                    $categories = $this->articles->getPublishedCategoryFacets()
                        ->map(fn (Category $category) => [
                            'id' => $category->id,
                            'name' => $this->resolveCategoryLabel($category, $locale) ?? __('articles.public.uncategorized'),
                            'count' => (int) $category->published_articles_count,
                        ])
                        ->values()
                        ->all();

                    $tags = $this->articles->getPublishedTagFacets()
                        ->map(fn (Tag $tag) => [
                            'name' => $this->resolveTagLabel($tag, $locale),
                            'count' => (int) $tag->published_articles_count,
                        ])
                        ->values()
                        ->all();

                    return [
                        'articles' => $articles,
                        'categories' => $categories,
                        'tags' => $tags,
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return ['articles' => [], 'categories' => [], 'tags' => []];
                }
            },
        );

        // The index exists in every active UI language → emit hreflang for each.
        // Fall back to the full supported set when no languages are configured.
        $activeCodes = Language::getActiveCodes() ?: ['ar', 'en', 'fr', 'tr', 'ur'];
        $indexUrl = route('public.articles');
        $hreflangAlternates = [];
        foreach ($activeCodes as $code) {
            $hreflangAlternates[$code] = $code === 'ar' ? $indexUrl : $indexUrl.'?lang='.$code;
        }

        return view('public.articles.index', [
            'articles' => $payload['articles'],
            'categories' => $payload['categories'],
            'tags' => $payload['tags'],
            'totalCount' => count($payload['articles']),
            'hreflangAlternates' => count($hreflangAlternates) > 1 ? $hreflangAlternates : [],
        ]);
    }

    /**
     * Single article reader.
     */
    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        $payload = Cache::flexible(
            "public_article_show_{$slug}_{$locale}",
            [1800, 3600],
            function () use ($slug, $locale): ?array {
                try {
                    $article = $this->articles->findBySlug($slug, $locale);

                    // Fall back to Arabic if no localized copy is ready in the active locale.
                    if ($article === null && $locale !== 'ar') {
                        $article = $this->articles->findBySlug($slug, 'ar');
                    }

                    if ($article === null) {
                        return null;
                    }

                    $articleLocale = $this->resolveLocale($article, $locale);

                    if ($articleLocale === null) {
                        return null;
                    }

                    $related = $this->articles->getRelatedForPublic($article->id, 6)
                        ->map(fn (Article $item) => $this->mapCard($item, $locale))
                        ->filter()
                        ->values()
                        ->all();

                    $adjacent = $this->resolveAdjacent($article, $locale);

                    return [
                        'article' => $this->mapDetail($article, $articleLocale, $locale),
                        'related' => $related,
                        'prev' => $adjacent['prev'],
                        'next' => $adjacent['next'],
                    ];
                } catch (Throwable $e) {
                    report($e);

                    return null;
                }
            },
        );

        if ($payload === null) {
            abort(404);
        }

        // View counting is handled by an async client call (registerView) so it
        // keeps working while the page payload above is cached for an hour.

        return view('public.articles.show', [
            'article' => $payload['article'],
            'related' => $payload['related'],
            'prev' => $payload['prev'] ?? null,
            'next' => $payload['next'] ?? null,
            'hreflangAlternates' => $payload['article']['alternates'] ?? [],
        ]);
    }

    /**
     * Register a view for an article (called async from the reader on load).
     * Session-guarded so refreshes within a session don't inflate the counter;
     * returns the fresh count for the client to render.
     */
    public function registerView(int $id): JsonResponse
    {
        $sessionKey = 'article_viewed_'.$id;

        // Already counted this session — nothing to do; client keeps its count.
        if (session()->has($sessionKey)) {
            return response()->json(['ok' => true]);
        }

        try {
            $views = $this->articles->incrementViews($id);
            session()->put($sessionKey, true);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['ok' => false], 200);
        }

        return response()->json(['ok' => true, 'views' => $views]);
    }

    /**
     * Map an Article to a compact card array for listings/carousels.
     */
    private function mapCard(Article $article, string $locale): ?array
    {
        $articleLocale = $this->resolveLocale($article, $locale);

        if ($articleLocale === null) {
            return null;
        }

        return [
            'id' => $article->id,
            'title' => $articleLocale->title,
            'slug' => $articleLocale->slug,
            'excerpt' => Str::limit(strip_tags((string) ($articleLocale->excerpt ?: $articleLocale->description)), 200),
            'image' => $this->resolveImage($article),
            'category' => $this->resolveCategoryName($article, $locale),
            'categoryId' => $article->category_id,
            'tags' => $this->resolveTagNames($article, $locale),
            'author' => optional($article->writer)->name,
            'readTime' => (int) $article->read_time,
            'views' => (int) $article->views,
            'isFeatured' => (bool) $article->is_featured,
            'date' => optional($article->created_at)->format('Y-m-d'),
            'dateLabel' => optional($article->created_at)->translatedFormat('d M Y'),
            'hijriDate' => $article->hijri_date,
            'timestamp' => optional($article->created_at)->timestamp ?? 0,
            'url' => route('public.articles.show', $articleLocale->slug),
        ];
    }

    /**
     * Map an Article + its locale to the full detail array for the reader.
     */
    private function mapDetail(Article $article, ArticleLocale $articleLocale, string $locale): array
    {
        $body = $articleLocale->getBody() ?: '';
        $toc = $this->buildToc($articleLocale, $body);

        return [
            'id' => $article->id,
            'title' => $articleLocale->title,
            'slug' => $articleLocale->slug,
            'excerpt' => trim(strip_tags((string) ($articleLocale->excerpt ?: $articleLocale->description))),
            'body' => $body,
            'toc' => $toc,
            'image' => $this->resolveImage($article, 'hero'),
            'category' => $this->resolveCategoryName($article, $locale),
            'tags' => $this->resolveTagNames($article, $locale),
            'author' => optional($article->writer)->name ?: __('articles.public.unknown_author'),
            'readTime' => (int) $article->read_time,
            'views' => (int) $article->views,
            'dateLabel' => optional($article->created_at)->translatedFormat('d F Y'),
            // ISO 8601 timestamps for schema.org (display label is not valid there).
            'datePublishedIso' => optional($article->published_at ?? $article->created_at)?->toAtomString(),
            'dateModifiedIso' => optional($article->updated_at)?->toAtomString(),
            'seoDescription' => Str::limit(strip_tags((string) ($articleLocale->excerpt ?: $articleLocale->description)), 155),
            'hijriDate' => $article->hijri_date,
            'references' => $this->normalizeReferences($article->references),
            'statistics' => $this->normalizeStatistics($article->statistics),
            'gallery' => $this->normalizeGallery($article, $locale),
            // The wizard collects a per-locale summary and attaches media, but
            // neither reached the reader: both were stored and then dropped here.
            'summary' => $this->normalizeSummary($articleLocale),
            'media' => $this->normalizeMedia($article),
            'videos' => $this->normalizeVideos($article, $locale),
            'url' => $this->localeUrl($articleLocale->slug, $articleLocale->locale),
            'canonical' => $this->localeUrl($articleLocale->slug, $articleLocale->locale),
            'alternates' => $this->buildArticleAlternates($article),
            'series' => $this->resolveSeries($article, $locale),
        ];
    }

    /**
     * Prev/next navigation for the reader. Articles that belong to a series
     * navigate along the series reading order (pivot position); standalone
     * articles fall back to date-adjacent published articles.
     *
     * @return array{prev: ?array, next: ?array}
     */
    private function resolveAdjacent(Article $article, string $locale): array
    {
        $series = $article->relationLoaded('series') ? $article->series->first() : null;

        if ($series !== null) {
            $ordered = $series->articles()
                ->where('status', 'published')
                ->whereHas('locales', fn ($q) => $q->where('is_ready', true))
                ->with([
                    'locales:id,article_id,locale,title,slug,excerpt,description,is_ready',
                    'writer:id,name',
                    'category:id,slug,type,status',
                    'category.translations:id,category_id,locale,name',
                ])
                ->get()
                ->values();

            $index = $ordered->search(fn (Article $a) => $a->id === $article->id);

            if ($index !== false) {
                $prev = $index > 0 ? $ordered[$index - 1] : null;
                $next = $index < $ordered->count() - 1 ? $ordered[$index + 1] : null;

                return [
                    'prev' => $prev ? $this->mapCard($prev, $locale) : null,
                    'next' => $next ? $this->mapCard($next, $locale) : null,
                ];
            }
        }

        $adjacent = $this->articles->getAdjacentForPublic($article->id, $article->created_at);

        return [
            'prev' => $adjacent['prev'] ? $this->mapCard($adjacent['prev'], $locale) : null,
            'next' => $adjacent['next'] ? $this->mapCard($adjacent['next'], $locale) : null,
        ];
    }

    /**
     * First series this article belongs to (reading-path banner on the reader).
     *
     * @return array{title: string, url: string}|null
     */
    private function resolveSeries(Article $article, string $locale): ?array
    {
        if (! $article->relationLoaded('series')) {
            return null;
        }

        $series = $article->series->first();

        if ($series === null) {
            return null;
        }

        $title = $series->translations->firstWhere('locale', $locale)?->title
            ?? $series->translations->firstWhere('locale', 'ar')?->title;

        if ($title === null) {
            return null;
        }

        // Ordered sibling list for the sidebar "series articles" card (same
        // visibility rules as series prev/next navigation).
        $articles = $series->articles()
            ->where('status', 'published')
            ->whereHas('locales', fn ($q) => $q->where('is_ready', true))
            ->with('locales:id,article_id,locale,title,slug,is_ready')
            ->get()
            ->map(function (Article $a) use ($locale, $article) {
                $al = $a->locales->firstWhere('locale', $locale)
                    ?? $a->locales->firstWhere('locale', 'ar')
                    ?? $a->locales->first();

                return $al === null ? null : [
                    'title' => $al->title,
                    'url' => $this->localeUrl($al->slug, $al->locale),
                    'current' => $a->id === $article->id,
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'title' => $title,
            'url' => route('public.series.show', $series->slug),
            'articles' => $articles,
        ];
    }

    /**
     * The editor's per-locale summary, stored inside the locale content JSON.
     */
    private function normalizeSummary(ArticleLocale $articleLocale): ?string
    {
        $content = $articleLocale->content;
        $summary = is_array($content) ? ($content['summary'] ?? null) : null;

        return filled($summary) ? trim((string) $summary) : null;
    }

    /**
     * Attached media, shaped for the reader.
     *
     * Streams through `public.articles.media`, which authorizes on the file
     * being attached to a published article rather than on an admin session —
     * the library itself stays encrypted and admin-only.
     *
     * @return array<int, array{id:int, title:string, type:string, mime:?string, url:string}>
     */
    private function normalizeMedia(Article $article): array
    {
        if (! $article->relationLoaded('mediaItems')) {
            return [];
        }

        return $article->mediaItems
            ->filter(fn ($media): bool => filled($media->public_key))
            ->map(fn ($media): array => [
                'id' => (int) $media->id,
                'title' => (string) ($media->alt_text ?: $media->original_filename),
                'type' => (string) $media->type,
                'mime' => $media->mime_type,
                'url' => route('public.articles.media', ['media' => $media->public_key]),
            ])
            ->values()
            ->all();
    }

    /**
     * Map the article's gallery rows into render-ready {url, caption} items.
     *
     * @return array<int, array{url: string, caption: ?string}>
     */
    private function normalizeGallery(Article $article, string $locale): array
    {
        if (! $article->relationLoaded('gallery')) {
            return [];
        }

        return $article->gallery
            ->map(fn ($item) => [
                'url' => asset('storage/'.ltrim((string) $item->image, '/')),
                'caption' => $item->getCaption($locale) ?: $item->getCaption('ar'),
            ])
            ->filter(fn ($item) => $item['url'] !== asset('storage/'))
            ->values()
            ->all();
    }

    /**
     * Shape the article's cited episodes into a render-ready video queue.
     *
     * `video_url` on an episode is whatever the YouTube UI produced — a watch
     * link, a share link, an embed src, a Shorts URL, sometimes with a playlist
     * or timestamp hanging off it. All carry the same eleven-character id, so
     * the id is extracted once here and every URL the view needs is built from
     * it. An episode whose URL yields no id is dropped rather than rendered as
     * a broken iframe.
     *
     * Unpublished episodes never reach the reader, so an article may safely
     * cite an episode that is still being prepared.
     *
     * `youtube-nocookie.com` is deliberate: the reader is not asked to accept
     * tracking to watch an embedded lecture.
     *
     * @return array<int, array{id:string, title:string, embedUrl:string, watchUrl:string, thumb:string}>
     */
    private function normalizeVideos(Article $article, string $locale): array
    {
        if (! $article->relationLoaded('episodes')) {
            return [];
        }

        return $article->episodes
            ->filter(fn ($episode): bool => (bool) $episode->is_published)
            ->map(function ($episode) use ($locale): ?array {
                $id = $this->youTubeId((string) $episode->video_url);

                if ($id === null) {
                    return null;
                }

                $title = $episode->getTranslation('title', $locale)
                    ?: $episode->getTranslation('title', 'ar')
                    ?: __('articles.public.video');

                return [
                    'id' => $id,
                    'title' => (string) $title,
                    'embedUrl' => 'https://www.youtube-nocookie.com/embed/'.$id.'?rel=0&modestbranding=1&playsinline=1',
                    'watchUrl' => 'https://www.youtube.com/watch?v='.$id,
                    'thumb' => 'https://i.ytimg.com/vi/'.$id.'/hqdefault.jpg',
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Pull the 11-character video id out of any YouTube URL form.
     */
    private function youTubeId(string $url): ?string
    {
        $patterns = [
            '~youtu\.be/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\.com/(?:embed|v|shorts|live)/([A-Za-z0-9_-]{11})~',
            '~youtube(?:-nocookie)?\.com/watch\?(?:.*&)?v=([A-Za-z0-9_-]{11})~',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url, $m) === 1) {
                return $m[1];
            }
        }

        // A bare id pasted on its own.
        return preg_match('~^[A-Za-z0-9_-]{11}$~', trim($url)) === 1 ? trim($url) : null;
    }

    /**
     * Normalize the stored references JSON into render-ready rows.
     *
     * @return array<int, array{name: string, url: ?string, type: ?string}>
     */
    private function normalizeReferences(mixed $references): array
    {
        if (! is_array($references)) {
            return [];
        }

        return collect($references)
            ->filter(fn ($row) => is_array($row) && ! empty($row['name']))
            ->map(fn ($row) => [
                'name' => (string) $row['name'],
                'url' => isset($row['url']) && $row['url'] !== '' ? (string) $row['url'] : null,
                'type' => isset($row['type']) && $row['type'] !== '' ? (string) $row['type'] : null,
            ])
            ->values()
            ->all();
    }

    /**
     * Normalize the stored statistics JSON into render-ready name/value rows.
     *
     * @return array<int, array{name: string, value: string}>
     */
    private function normalizeStatistics(mixed $statistics): array
    {
        if (! is_array($statistics)) {
            return [];
        }

        return collect($statistics)
            ->filter(fn ($row) => is_array($row) && ! empty($row['name']) && isset($row['value']) && $row['value'] !== '')
            ->map(fn ($row) => [
                'name' => (string) $row['name'],
                'value' => (string) $row['value'],
            ])
            ->values()
            ->all();
    }

    /**
     * Build a public article URL for a given locale. The Arabic default lives at
     * the bare slug URL; other locales carry an explicit ?lang= so a crawler that
     * follows the hreflang/canonical link is served that language's content.
     */
    private function localeUrl(string $slug, string $locale): string
    {
        $url = route('public.articles.show', $slug);

        return $locale === 'ar' ? $url : $url.'?lang='.$locale;
    }

    /**
     * hreflang alternates for an article, built from its READY locales only (each
     * with that locale's own slug). Returns [] for single-language articles so we
     * never emit a pointless self-only hreflang set.
     *
     * @return array<string, string>
     */
    private function buildArticleAlternates(Article $article): array
    {
        $ready = ($article->relationLoaded('locales') ? $article->locales : $article->locales()->get())
            ->filter(fn (ArticleLocale $row) => (bool) $row->is_ready && $row->slug);

        if ($ready->count() < 2) {
            return [];
        }

        return $ready
            ->sortBy(fn (ArticleLocale $row) => $row->locale === 'ar' ? 0 : 1)
            ->mapWithKeys(fn (ArticleLocale $row) => [$row->locale => $this->localeUrl($row->slug, $row->locale)])
            ->all();
    }

    /**
     * Resolve the best-matching locale row, preferring the active locale then Arabic.
     */
    private function resolveLocale(Article $article, string $locale): ?ArticleLocale
    {
        $locales = $article->relationLoaded('locales') ? $article->locales : $article->locales()->get();

        return $locales->firstWhere('locale', $locale)
            ?? $locales->firstWhere('locale', 'ar')
            ?? $locales->first();
    }

    /**
     * Resolve thumbnail URL with a deterministic fallback so cards never break.
     * $size picks the derived copy matched to the render context ('card' for
     * listings, 'hero' for the article page / og:image).
     */
    private function resolveImage(Article $article, string $size = 'card'): string
    {
        if (! empty($article->thumb)) {
            return $article->thumbUrl($size);
        }

        $fallbacks = ['site/article-1.webp', 'site/article-2.webp', 'site/article-3.webp'];

        return asset('images/'.$fallbacks[$article->id % count($fallbacks)]);
    }

    /**
     * Resolve the localized primary-category name.
     */
    private function resolveCategoryName(Article $article, string $locale): ?string
    {
        $category = $article->category;

        if ($category === null) {
            return null;
        }

        return $this->resolveCategoryLabel($category, $locale);
    }

    /**
     * Resolve the localized label for a category model (active locale, then Arabic).
     */
    private function resolveCategoryLabel(Category $category, string $locale): ?string
    {
        try {
            return $category->getTranslation('name', $locale)
                ?: $category->getTranslation('name', 'ar')
                ?: null;
        } catch (Throwable $e) {
            report($e);

            return null;
        }
    }

    /**
     * Resolve the localized label for a tag model (active locale, Arabic, then slug).
     */
    private function resolveTagLabel(Tag $tag, string $locale): string
    {
        try {
            return $tag->getTranslation('name', $locale)
                ?: $tag->getTranslation('name', 'ar')
                ?: $tag->slug;
        } catch (Throwable $e) {
            report($e);

            return $tag->slug;
        }
    }

    /**
     * Resolve localized tag names for a card.
     *
     * @return array<int, string>
     */
    private function resolveTagNames(Article $article, string $locale): array
    {
        if (! $article->relationLoaded('tags')) {
            return [];
        }

        return $article->tags
            ->map(fn (Tag $tag): string => $this->resolveTagLabel($tag, $locale))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Build the table-of-contents entries used for the scroll-spy sidebar.
     *
     * Prefers an editor-supplied TOC; otherwise derives anchors from H2/H3
     * headings in the body and rewrites the body to carry matching ids.
     *
     * @return array<int, array{id: string, label: string, level: int}>
     */
    private function buildToc(ArticleLocale $articleLocale, string &$body): array
    {
        // Always derive anchors from the rendered body so every TOC entry has a
        // real scroll target (an existing heading id is reused, otherwise a
        // sequential one is injected). The editor-curated TOC (getToc) is only a
        // display fallback for bodies that carry no headings.
        if ($body !== '' && Str::contains($body, ['<h2', '<h3'])) {
            $toc = [];
            $index = 0;

            $body = preg_replace_callback(
                '/<(h2|h3)([^>]*)>(.*?)<\/\1>/is',
                function (array $match) use (&$toc, &$index): string {
                    $tag = strtolower($match[1]);
                    $attrs = $match[2];
                    $label = trim(strip_tags($match[3]));

                    if ($label === '') {
                        return $match[0];
                    }

                    if (preg_match('/\bid=["\']([^"\']+)["\']/i', $attrs, $idMatch)) {
                        $id = $idMatch[1];
                    } else {
                        $id = 'section-'.$index;
                        $attrs .= ' id="'.$id.'"';
                    }
                    $index++;

                    $toc[] = [
                        'id' => $id,
                        'label' => $label,
                        'level' => $tag === 'h3' ? 3 : 2,
                    ];

                    return "<{$tag}{$attrs}>{$match[3]}</{$tag}>";
                },
                $body,
            ) ?? $body;

            if ($toc !== []) {
                return $toc;
            }
        }

        $stored = $articleLocale->getToc();

        if (is_array($stored) && $stored !== []) {
            return collect($stored)
                ->map(fn ($item, $i) => [
                    'id' => Str::slug(($item['title'] ?? $item['label'] ?? 'section-'.$i), '-', null) ?: 'section-'.$i,
                    'label' => $item['title'] ?? $item['label'] ?? '',
                    'level' => (int) ($item['level'] ?? 2),
                ])
                ->filter(fn ($item) => $item['label'] !== '')
                ->values()
                ->all();
        }

        return [];
    }
}
