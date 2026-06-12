<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Blog\Services;

use App\Domain\Blog\Services\SlugGeneratorService;
use App\Models\Article;
use App\Models\ArticleLocale;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SlugGeneratorServiceTest extends TestCase
{
    use RefreshDatabase;

    private SlugGeneratorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SlugGeneratorService;
    }

    public function test_generates_slug_from_english_title(): void
    {
        $slug = $this->service->generate('Hello World', 'en');

        $this->assertSame('hello-world', $slug);
    }

    public function test_generates_slug_from_title_with_special_characters(): void
    {
        $slug = $this->service->generate('The Birth of the Prophet', 'en');

        $this->assertSame('the-birth-of-the-prophet', $slug);
    }

    public function test_generates_unique_slug_when_conflict_exists(): void
    {
        $article = Article::query()->create([]);
        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'slug' => 'test-article',
            'title' => 'Test Article',
            'is_ready' => false,
        ]);

        $slug = $this->service->generate('Test Article', 'en');

        $this->assertSame('test-article-1', $slug);
    }

    public function test_generates_incremented_slug_for_multiple_conflicts(): void
    {
        $article1 = Article::query()->create([]);
        $article2 = Article::query()->create([]);

        ArticleLocale::query()->create([
            'article_id' => $article1->id,
            'locale' => 'en',
            'slug' => 'migration',
            'title' => 'Migration',
            'is_ready' => false,
        ]);

        ArticleLocale::query()->create([
            'article_id' => $article2->id,
            'locale' => 'en',
            'slug' => 'migration-1',
            'title' => 'Migration',
            'is_ready' => false,
        ]);

        $slug = $this->service->generate('Migration', 'en');

        $this->assertSame('migration-2', $slug);
    }

    public function test_excludes_own_article_when_updating(): void
    {
        $article = Article::query()->create([]);
        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'en',
            'slug' => 'existing-slug',
            'title' => 'Existing Slug',
            'is_ready' => false,
        ]);

        // When updating this article's own locale, it should not see its own slug as a conflict
        $slug = $this->service->generate('Existing Slug', 'en', excludeArticleId: $article->id);

        $this->assertSame('existing-slug', $slug);
    }

    public function test_different_locales_can_have_the_same_slug(): void
    {
        $article = Article::query()->create([]);
        ArticleLocale::query()->create([
            'article_id' => $article->id,
            'locale' => 'ar',
            'slug' => 'migration',
            'title' => 'الهجرة',
            'is_ready' => false,
        ]);

        // Same slug in a different locale should not conflict
        $slug = $this->service->generate('Migration', 'en');

        $this->assertSame('migration', $slug);
    }
}
