<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Blog\Models\Category;
use App\Domain\Blog\Models\Tag;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleLocale;
use App\Repositories\ArticleRepository;
use Illuminate\Contracts\View\View;
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

        $payload = Cache::remember(
            "public_articles_index_{$locale}",
            1800,
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

        return view('public.articles.index', [
            'articles' => $payload['articles'],
            'categories' => $payload['categories'],
            'tags' => $payload['tags'],
            'totalCount' => count($payload['articles']),
        ]);
    }

    /**
     * Single article reader.
     */
    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_article_show_{$slug}_{$locale}",
            1800,
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

                    return [
                        'article' => $this->mapDetail($article, $articleLocale, $locale),
                        'related' => $related,
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

        // View counting is a write — kept out of the cached closure, fire-and-forget.
        // Session-guarded so refreshes within a session don't inflate the counter.
        $viewedSessionKey = 'article_viewed_'.$payload['article']['id'];

        if (! session()->has($viewedSessionKey)) {
            try {
                $this->articles->incrementViews($payload['article']['id']);
                session()->put($viewedSessionKey, true);
            } catch (Throwable) {
                // Non-critical; ignore counter failures.
            }
        }

        return view('public.articles.show', [
            'article' => $payload['article'],
            'related' => $payload['related'],
        ]);
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
            'excerpt' => $articleLocale->excerpt ?: $articleLocale->description,
            'body' => $body,
            'toc' => $toc,
            'image' => $this->resolveImage($article),
            'category' => $this->resolveCategoryName($article, $locale),
            'tags' => $this->resolveTagNames($article, $locale),
            'author' => optional($article->writer)->name ?: __('articles.public.unknown_author'),
            'readTime' => (int) $article->read_time,
            'views' => (int) $article->views,
            'dateLabel' => optional($article->created_at)->translatedFormat('d F Y'),
            'hijriDate' => $article->hijri_date,
            'url' => route('public.articles.show', $articleLocale->slug),
        ];
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
     */
    private function resolveImage(Article $article): string
    {
        if (! empty($article->thumb)) {
            return asset('storage/'.ltrim($article->thumb, '/'));
        }

        $fallbacks = ['article-1.png', 'article-2.png', 'article-3.png'];

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
        } catch (Throwable) {
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
        } catch (Throwable) {
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

        if ($body === '' || ! Str::contains($body, ['<h2', '<h3'])) {
            return [];
        }

        $toc = [];
        $index = 0;

        $body = preg_replace_callback(
            '/<(h2|h3)([^>]*)>(.*?)<\/\1>/is',
            function (array $match) use (&$toc, &$index): string {
                $tag = strtolower($match[1]);
                $label = trim(strip_tags($match[3]));

                if ($label === '') {
                    return $match[0];
                }

                $id = 'section-'.$index;
                $index++;

                $toc[] = [
                    'id' => $id,
                    'label' => $label,
                    'level' => $tag === 'h3' ? 3 : 2,
                ];

                return "<{$tag}{$match[2]} id=\"{$id}\">{$match[3]}</{$tag}>";
            },
            $body,
        ) ?? $body;

        return $toc;
    }
}
