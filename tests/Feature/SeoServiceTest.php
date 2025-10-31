<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use App\Services\SeoService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * SEO Service Feature Tests
 *
 * Tests the complete SEO service functionality including:
 * - Metadata generation
 * - Validation
 * - Multi-language support
 * - Suggestions engine
 */
class SeoServiceTest extends TestCase
{
    use RefreshDatabase;

    private SeoService $seoService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seoService = app(SeoService::class);
    }

    /**
     * Test: SEO metadata generation for published article
     */
    public function test_generates_complete_seo_metadata_for_published_article(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
            'title' => ['ar' => 'عنوان المقال الرائع', 'en' => 'Amazing Article Title'],
            'excerpt' => ['ar' => 'مقتطف من المقال', 'en' => 'Article excerpt'],
            'content' => ['ar' => 'محتوى المقال الكامل', 'en' => 'Full article content'],
            'seo_keywords' => 'keyword1, keyword2, keyword3',
        ]);

        // Act
        $seo = $this->seoService->generateSeoData($article, 'ar');

        // Assert
        $this->assertNotEmpty($seo->title);
        $this->assertNotEmpty($seo->description);
        $this->assertEquals('keyword1, keyword2, keyword3', $seo->keywords);
        $this->assertNotEmpty($seo->canonicalUrl);
        $this->assertIsArray($seo->openGraph);
        $this->assertIsArray($seo->twitterCard);
        $this->assertIsArray($seo->jsonLd);
        $this->assertEquals('ar', $seo->locale);
    }

    /**
     * Test: OpenGraph tags generation
     */
    public function test_generates_opengraph_tags(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // Act
        $ogTags = $this->seoService->generateOpenGraphTags($article, 'ar');

        // Assert
        $this->assertArrayHasKey('og:type', $ogTags);
        $this->assertArrayHasKey('og:title', $ogTags);
        $this->assertArrayHasKey('og:description', $ogTags);
        $this->assertArrayHasKey('og:url', $ogTags);
        $this->assertArrayHasKey('og:locale', $ogTags);
        $this->assertEquals('article', $ogTags['og:type']);
        $this->assertEquals('ar_SA', $ogTags['og:locale']);
    }

    /**
     * Test: Twitter Card tags generation
     */
    public function test_generates_twitter_card_tags(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
        ]);

        // Act
        $twitterTags = $this->seoService->generateTwitterCardTags($article, 'ar');

        // Assert
        $this->assertArrayHasKey('twitter:card', $twitterTags);
        $this->assertArrayHasKey('twitter:title', $twitterTags);
        $this->assertArrayHasKey('twitter:description', $twitterTags);
        $this->assertEquals('summary_large_image', $twitterTags['twitter:card']);
    }

    /**
     * Test: JSON-LD generation
     */
    public function test_generates_jsonld_structured_data(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
        ]);

        // Act
        $jsonLd = $this->seoService->generateJsonLd($article);

        // Assert
        $this->assertJson($jsonLd);
        $data = json_decode($jsonLd, true);
        $this->assertEquals('https://schema.org', $data['@context']);
        $this->assertEquals('Article', $data['@type']);
        $this->assertArrayHasKey('headline', $data);
        $this->assertArrayHasKey('author', $data);
        $this->assertArrayHasKey('publisher', $data);
    }

    /**
     * Test: SEO validation passes for good article
     */
    public function test_validates_article_with_good_seo(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
            'title' => ['ar' => str_repeat('عنوان رائع ', 5)], // ~50 chars
            'excerpt' => ['ar' => str_repeat('مقتطف جيد ', 15)], // ~150 chars
            'content' => ['ar' => str_repeat('محتوى طويل جدا ', 100)], // ~1000 chars
            'seo_keywords' => 'keyword1, keyword2, keyword3, keyword4',
            'featured_image' => 'images/test.jpg',
        ]);

        // Act
        $validation = $this->seoService->validateSeoData($article, 'ar');

        // Assert
        $this->assertTrue($validation->isValid);
        $this->assertGreaterThan(70, $validation->seoScore);
        $this->assertContains($validation->getGrade(), ['A', 'B', 'C']);
    }

    /**
     * Test: SEO validation fails for poor article
     */
    public function test_validates_article_with_poor_seo(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_DRAFT,
            'title' => ['ar' => 'قصير'], // Too short
            'excerpt' => ['ar' => null],
            'content' => ['ar' => 'قصير جدا'], // Too short
            'seo_keywords' => null,
            'featured_image' => null,
        ]);

        // Act
        $validation = $this->seoService->validateSeoData($article, 'ar');

        // Assert
        $this->assertFalse($validation->isValid);
        $this->assertGreaterThan(0, count($validation->errors));
        $this->assertLessThan(60, $validation->seoScore);
    }

    /**
     * Test: Suggestions generation
     */
    public function test_generates_seo_suggestions(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'category_id' => null,
            'seo_keywords' => null,
        ]);

        // Act
        $suggestions = $this->seoService->generateSuggestions($article, 'ar');

        // Assert
        $this->assertIsArray($suggestions);
        $this->assertGreaterThan(0, count($suggestions));
    }

    /**
     * Test: Sitemap entry generation
     */
    public function test_generates_sitemap_entry_for_published_article(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'status' => Article::STATUS_PUBLISHED,
            'published_at' => now(),
            'featured' => true,
        ]);

        // Act
        $entry = $this->seoService->generateSitemapEntry($article);

        // Assert
        $this->assertArrayHasKey('loc', $entry);
        $this->assertArrayHasKey('lastmod', $entry);
        $this->assertArrayHasKey('changefreq', $entry);
        $this->assertArrayHasKey('priority', $entry);
        $this->assertEquals('0.9', $entry['priority']); // Featured article
    }

    /**
     * Test: Multi-language support
     */
    public function test_supports_multiple_languages(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create([
            'user_id' => $user->id,
            'title' => [
                'ar' => 'العنوان العربي',
                'en' => 'English Title',
                'fr' => 'Titre Français',
            ],
        ]);

        $locales = ['ar', 'en', 'fr'];

        // Act & Assert
        foreach ($locales as $locale) {
            $seo = $this->seoService->generateSeoData($article, $locale);
            $this->assertEquals($locale, $seo->locale);
            $this->assertNotEmpty($seo->title);
        }
    }

    /**
     * Test: Text truncation
     */
    public function test_truncates_long_text_correctly(): void
    {
        // Arrange
        $longText = str_repeat('This is a very long text that needs truncation. ', 20);

        // Act
        $truncated = $this->seoService->truncateText($longText, 100);

        // Assert
        $this->assertLessThanOrEqual(100, mb_strlen($truncated));
        $this->assertStringEndsWith('...', $truncated);
    }

    /**
     * Test: Excerpt generation from content
     */
    public function test_generates_excerpt_from_content(): void
    {
        // Arrange
        $content = '<p>This is a paragraph with <strong>HTML tags</strong> that should be stripped.</p>';

        // Act
        $excerpt = $this->seoService->generateExcerpt($content, 50);

        // Assert
        $this->assertLessThanOrEqual(50, mb_strlen($excerpt));
        $this->assertStringNotContainsString('<', $excerpt);
        $this->assertStringNotContainsString('>', $excerpt);
    }

    /**
     * Test: Locale code mapping
     */
    public function test_maps_locale_to_opengraph_code(): void
    {
        // Assert
        $this->assertEquals('ar_SA', $this->seoService->getLocaleCode('ar'));
        $this->assertEquals('en_US', $this->seoService->getLocaleCode('en'));
        $this->assertEquals('fr_FR', $this->seoService->getLocaleCode('fr'));
        $this->assertEquals('tr_TR', $this->seoService->getLocaleCode('tr'));
        $this->assertEquals('ur_PK', $this->seoService->getLocaleCode('ur'));
    }

    /**
     * Test: SeoMetadata rendering
     */
    public function test_renders_metadata_as_html(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id]);
        $seo = $this->seoService->generateSeoData($article, 'ar');

        // Act
        $html = $seo->renderMetaTags();

        // Assert
        $this->assertStringContainsString('<meta name="description"', $html);
        $this->assertStringContainsString('<meta name="keywords"', $html);
        $this->assertStringContainsString('<link rel="canonical"', $html);
        $this->assertStringContainsString('<meta property="og:', $html);
        $this->assertStringContainsString('<meta name="twitter:', $html);
    }

    /**
     * Test: Exception thrown for invalid locale
     */
    public function test_throws_exception_for_invalid_locale(): void
    {
        // Arrange
        $user = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $user->id]);

        // Assert
        $this->expectException(\App\Exceptions\SeoException::class);

        // Act
        $this->seoService->generateSeoData($article, 'invalid_locale');
    }
}
