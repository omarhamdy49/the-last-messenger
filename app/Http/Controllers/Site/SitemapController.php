<?php

declare(strict_types=1);

namespace App\Http\Controllers\Site;

use App\Domain\Shared\Models\Language;
use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Repositories\ArticleRepository;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

final class SitemapController extends Controller
{
    public function __construct(
        private readonly ArticleRepository $articles,
    ) {}

    /**
     * XML sitemap for the public articles surface (index + every published
     * article), with hreflang alternates per ready locale. Cached for an hour.
     */
    public function articles(): Response
    {
        $entries = Cache::remember('sitemap_articles', 3600, fn (): array => $this->buildEntries());

        return response()
            ->view('public.sitemap', ['entries' => $entries])
            ->header('Content-Type', 'application/xml');
    }

    /**
     * @return array<int, array{loc: string, lastmod: ?string, alternates: array<string, string>}>
     */
    private function buildEntries(): array
    {
        $activeCodes = Language::getActiveCodes() ?: ['ar', 'en', 'fr', 'tr', 'ur'];
        $indexUrl = route('public.articles');

        // Index page: one entry, alternates for every active language.
        $indexAlternates = [];
        foreach ($activeCodes as $code) {
            $indexAlternates[$code] = $this->localeUrl($indexUrl, $code);
        }

        $entries = [[
            'loc' => $indexUrl,
            'lastmod' => null,
            'alternates' => count($indexAlternates) > 1 ? $indexAlternates : [],
        ]];

        foreach ($this->articles->getPublishedForSitemap() as $article) {
            /** @var Article $article */
            $alternates = [];
            foreach ($article->locales as $row) {
                if ($row->slug) {
                    $alternates[$row->locale] = $this->localeUrl(route('public.articles.show', $row->slug), $row->locale);
                }
            }

            if ($alternates === []) {
                continue;
            }

            // Canonical entry uses the Arabic version when ready, else the first.
            $primary = $alternates['ar'] ?? reset($alternates);

            $entries[] = [
                'loc' => $primary,
                'lastmod' => optional($article->updated_at)?->toAtomString(),
                'alternates' => count($alternates) > 1 ? $alternates : [],
            ];
        }

        return $entries;
    }

    /**
     * Arabic default lives at the bare URL; other locales carry ?lang= so the
     * sitemap URL resolves to that language (mirrors the on-page hreflang).
     */
    private function localeUrl(string $url, string $locale): string
    {
        return $locale === 'ar' ? $url : $url.'?lang='.$locale;
    }
}
