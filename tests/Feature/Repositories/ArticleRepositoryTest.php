<?php

declare(strict_types=1);

namespace Tests\Feature\Repositories;

use App\Models\Article;
use App\Models\ArticleLocale;
use App\Repositories\ArticleRepository;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration tests for ArticleRepository.
 *
 * No factories exist for Article/ArticleLocale, so rows are inserted via
 * DB::table() or direct model creation (the models are mass-assignable).
 *
 * Focus:
 *  - getAllWithFilters() returns paginated results and respects filters.
 *  - Eager loading (locales, writer, category, category.translations) does not
 *    cause N+1 queries: a page of 20 articles must complete in < 5 queries.
 *  - findBySlug() resolves the correct Article.
 */
class ArticleRepositoryTest extends TestCase
{
    use RefreshDatabase;

    // ---------------------------------------------------------------------------
    // Helpers
    // ---------------------------------------------------------------------------

    /**
     * Insert an Article row directly and optionally a matching ArticleLocale.
     * Returns the Article model.
     */
    private function createArticle(
        array $articleData = [],
        ?array $localeData = null
    ): Article {
        $defaults = [
            'read_time' => 5,
            'views' => 0,
            'is_featured' => false,
            'is_pinned' => false,
            'status' => 'published',
        ];

        $article = Article::create(array_merge($defaults, $articleData));

        if ($localeData !== null) {
            $localeDefaults = [
                'article_id' => $article->id,
                'locale' => 'ar',
                'title' => 'عنوان المقال',
                'slug' => 'article-slug-'.$article->id,
                'is_ready' => true,
            ];

            ArticleLocale::create(array_merge($localeDefaults, $localeData));
        }

        return $article->fresh();
    }

    /**
     * Create $count articles, each with a ready Arabic locale, using unique slugs.
     */
    private function createArticles(int $count): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $this->createArticle(
                ['views' => $i * 10],
                [
                    'locale' => 'ar',
                    'title' => "عنوان المقال {$i}",
                    'slug' => "test-article-{$i}",
                    'is_ready' => true,
                ]
            );
        }
    }

    // ---------------------------------------------------------------------------
    // getAllWithFilters() — basic pagination
    // ---------------------------------------------------------------------------

    public function test_get_all_with_filters_returns_paginated_results(): void
    {
        $this->createArticles(5);

        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters([], null, 10);

        $this->assertInstanceOf(LengthAwarePaginator::class, $results);
        $this->assertEquals(5, $results->total());
    }

    public function test_get_all_with_filters_returns_empty_when_no_articles(): void
    {
        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters();

        $this->assertEquals(0, $results->total());
    }

    // ---------------------------------------------------------------------------
    // getAllWithFilters() — N+1 check
    // ---------------------------------------------------------------------------

    public function test_get_all_with_filters_does_not_cause_n_plus_one_for_page_of_20(): void
    {
        $this->createArticles(20);

        $queryCount = 0;
        DB::listen(static function () use (&$queryCount): void {
            $queryCount++;
        });

        $repo = app(ArticleRepository::class);
        $repo->getAllWithFilters([], null, 20);

        // With batch eager loading (locales, writer, category, categories) the
        // query count should be well below 5 regardless of page size.
        // A naive loop would produce 20+ queries for 20 articles.
        $this->assertLessThan(
            5,
            $queryCount,
            "getAllWithFilters() issued {$queryCount} queries for 20 articles — check eager loading."
        );
    }

    // ---------------------------------------------------------------------------
    // getAllWithFilters() — status filter
    // ---------------------------------------------------------------------------

    public function test_get_all_with_filters_status_published_returns_articles_with_ready_locale(): void
    {
        // 2 published (has ready locale), 1 draft (no locale)
        $this->createArticle([], ['locale' => 'ar', 'title' => 'مقال أول', 'slug' => 'slug-1', 'is_ready' => true]);
        $this->createArticle([], ['locale' => 'ar', 'title' => 'مقال ثاني', 'slug' => 'slug-2', 'is_ready' => true]);
        $this->createArticle(); // no locale → effectively a draft

        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters(['status' => 'published'], null, 15);

        $this->assertEquals(2, $results->total());
    }

    public function test_get_all_with_filters_status_draft_returns_articles_without_ready_locale(): void
    {
        $this->createArticle([], ['locale' => 'ar', 'title' => 'مقال منشور', 'slug' => 'slug-pub', 'is_ready' => true]);
        $this->createArticle(); // no locale row → draft
        $this->createArticle(); // no locale row → draft

        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters(['status' => 'draft'], null, 15);

        $this->assertEquals(2, $results->total());
    }

    // ---------------------------------------------------------------------------
    // getAllWithFilters() — is_featured flag
    // ---------------------------------------------------------------------------

    public function test_get_all_with_filters_is_featured_returns_only_featured_articles(): void
    {
        $this->createArticle(['is_featured' => true], ['title' => 'مميز', 'slug' => 'slug-feat-1', 'is_ready' => true]);
        $this->createArticle(['is_featured' => true], ['title' => 'مميز 2', 'slug' => 'slug-feat-2', 'is_ready' => true]);
        $this->createArticle(['is_featured' => false], ['title' => 'عادي', 'slug' => 'slug-norm', 'is_ready' => true]);

        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters(['is_featured' => true], null, 15);

        $this->assertEquals(2, $results->total());
        foreach ($results->items() as $article) {
            $this->assertTrue($article->is_featured);
        }
    }

    // ---------------------------------------------------------------------------
    // getAllWithFilters() — per-page cap
    // ---------------------------------------------------------------------------

    public function test_get_all_with_filters_respects_per_page_parameter(): void
    {
        $this->createArticles(10);

        $repo = app(ArticleRepository::class);
        $results = $repo->getAllWithFilters([], null, 3);

        $this->assertCount(3, $results->items());
        $this->assertEquals(10, $results->total());
    }

    // ---------------------------------------------------------------------------
    // findBySlug()
    // ---------------------------------------------------------------------------

    public function test_find_by_slug_returns_the_matching_article(): void
    {
        $article = $this->createArticle(
            [],
            ['locale' => 'ar', 'title' => 'المقال المطلوب', 'slug' => 'target-slug', 'is_ready' => true]
        );

        $repo = app(ArticleRepository::class);
        $result = $repo->findBySlug('target-slug', 'ar');

        $this->assertNotNull($result);
        $this->assertEquals($article->id, $result->id);
    }

    public function test_find_by_slug_returns_null_for_non_existent_slug(): void
    {
        $repo = app(ArticleRepository::class);
        $result = $repo->findBySlug('does-not-exist', 'ar');

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_null_when_locale_does_not_match(): void
    {
        $this->createArticle(
            [],
            ['locale' => 'ar', 'title' => 'عربي فقط', 'slug' => 'arabic-only', 'is_ready' => true]
        );

        $repo = app(ArticleRepository::class);
        // Requesting the English locale — no English locale row exists
        $result = $repo->findBySlug('arabic-only', 'en');

        $this->assertNull($result);
    }

    public function test_find_by_slug_returns_null_for_non_ready_locale(): void
    {
        $this->createArticle(
            [],
            ['locale' => 'ar', 'title' => 'مسودة', 'slug' => 'draft-slug', 'is_ready' => false]
        );

        $repo = app(ArticleRepository::class);
        $result = $repo->findBySlug('draft-slug', 'ar');

        $this->assertNull($result);
    }

    // ---------------------------------------------------------------------------
    // getArticlesByStatus()
    // ---------------------------------------------------------------------------

    public function test_get_articles_by_status_featured_returns_flagged_articles(): void
    {
        $this->createArticle(['is_featured' => true], ['slug' => 'f1', 'is_ready' => true]);
        $this->createArticle(['is_featured' => false], ['slug' => 'f2', 'is_ready' => true]);

        $repo = app(ArticleRepository::class);
        $results = $repo->getArticlesByStatus('featured');

        $this->assertEquals(1, $results->total());
        $this->assertTrue($results->first()->is_featured);
    }
}
