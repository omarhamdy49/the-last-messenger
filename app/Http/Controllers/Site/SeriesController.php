<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Blog\Models\Series;
use App\Http\Controllers\Controller;
use App\Models\Article;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use Throwable;

/**
 * Public article series: an ordered reading path of related articles.
 * Mirrors the public articles surface (1h cache, locale-aware mapping,
 * only published articles whose locale is marked ready).
 */
final class SeriesController extends Controller
{
    public function index(): View
    {
        $locale = app()->getLocale();

        $series = Cache::remember(
            "public_series_index_{$locale}",
            3600,
            function () use ($locale): array {
                try {
                    return Series::query()
                        ->with('translations')
                        ->withCount(['articles as published_articles_count' => function ($q) {
                            $q->where('status', 'published')
                                ->whereHas('locales', fn ($l) => $l->where('is_ready', true));
                        }])
                        ->orderByDesc('created_at')
                        ->get()
                        ->map(fn (Series $s) => $this->mapSeries($s, $locale))
                        ->filter()
                        ->values()
                        ->all();
                } catch (Throwable $e) {
                    report($e);

                    return [];
                }
            },
        );

        return view('public.series.index', ['series' => $series]);
    }

    public function show(string $slug): View
    {
        $locale = app()->getLocale();

        $payload = Cache::remember(
            "public_series_show_{$slug}_{$locale}",
            3600,
            function () use ($slug, $locale): ?array {
                try {
                    $series = Series::query()
                        ->with('translations')
                        ->where('slug', $slug)
                        ->first();

                    if ($series === null) {
                        return null;
                    }

                    $articles = $series->articles()
                        ->where('status', 'published')
                        ->whereHas('locales', fn ($q) => $q->where('is_ready', true))
                        ->with([
                            'locales' => fn ($q) => $q->whereIn('locale', array_unique([$locale, 'ar'])),
                            'category.translations',
                        ])
                        ->get()
                        ->map(fn (Article $a) => $this->mapArticle($a, $locale))
                        ->filter()
                        ->values()
                        ->all();

                    return [
                        'series' => $this->mapSeries($series, $locale, withCount: count($articles)),
                        'articles' => $articles,
                        'totalReadTime' => array_sum(array_column($articles, 'readTime')),
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

        return view('public.series.show', $payload);
    }

    /** @return array<string, mixed>|null */
    private function mapSeries(Series $series, string $locale, ?int $withCount = null): ?array
    {
        $t = $series->translations->firstWhere('locale', $locale)
            ?? $series->translations->firstWhere('locale', 'ar');

        if ($t === null) {
            return null;
        }

        return [
            'id' => $series->id,
            'slug' => $series->slug,
            'title' => $t->title,
            'description' => trim(strip_tags((string) $t->description)),
            'thumbnail' => $series->thumbnail
                ? (Str::startsWith($series->thumbnail, ['http', '/']) ? $series->thumbnail : asset('storage/'.$series->thumbnail))
                : null,
            'articlesCount' => $withCount ?? (int) ($series->published_articles_count ?? $series->total_articles),
            'url' => route('public.series.show', $series->slug),
            'dateLabel' => optional($series->created_at)->translatedFormat('d F Y'),
        ];
    }

    /** @return array<string, mixed>|null */
    private function mapArticle(Article $article, string $locale): ?array
    {
        $l = $article->locales->firstWhere('locale', $locale)
            ?? $article->locales->firstWhere('locale', 'ar');

        if ($l === null || ! $l->is_ready) {
            return null;
        }

        $category = $article->category;
        $categoryName = $category
            ? ($category->translations->firstWhere('locale', $locale)?->name
                ?? $category->translations->firstWhere('locale', 'ar')?->name)
            : null;

        return [
            'id' => $article->id,
            'title' => $l->title,
            'excerpt' => Str::limit(trim(strip_tags((string) ($l->excerpt ?: $l->description))), 180),
            'image' => $article->thumb
                ? asset('storage/'.ltrim($article->thumb, '/'))
                : asset('images/site/article-'.(($article->id % 3) + 1).'.webp'),
            'url' => route('public.articles.show', $l->slug),
            'category' => $categoryName,
            'readTime' => (int) $article->read_time,
            'views' => (int) $article->views,
            'dateLabel' => optional($article->created_at)->translatedFormat('d F Y'),
            'position' => (int) ($article->pivot->position ?? 0),
        ];
    }
}
