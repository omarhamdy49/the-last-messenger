<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Models\StaticPage;
use App\Domain\Content\Services\StaticPageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageServiceTest extends TestCase
{
    use RefreshDatabase;

    private StaticPageService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(StaticPageService::class);
    }

    private function createPage(string $slug, array $attributes = []): StaticPage
    {
        /** @var StaticPage $page */
        $page = StaticPage::query()->create(array_merge([
            'slug' => $slug,
            'is_active' => true,
            'published_at' => now()->subDay(),
        ], $attributes));

        $page->syncTranslations([
            'title' => ['ar' => 'عن المشروع', 'en' => 'About the Project'],
            'body' => ['ar' => '<p>محتوى عربي</p>', 'en' => '<p>English body</p>'],
            'seo_title' => ['ar' => 'عن المشروع — خاتم المرسلين'],
            'seo_description' => ['ar' => 'وصف الصفحة'],
        ]);

        return $page;
    }

    public function test_finds_published_page_by_slug_for_locale(): void
    {
        $this->createPage('about');

        $page = $this->service->getBySlug('about', 'en');

        $this->assertNotNull($page);
        $this->assertSame('about', $page['slug']);
        $this->assertSame('About the Project', $page['title']);
        $this->assertSame('<p>English body</p>', $page['body']);
    }

    public function test_falls_back_to_arabic_for_missing_locale_fields(): void
    {
        $this->createPage('about');

        $page = $this->service->getBySlug('about', 'en');

        // seo_title has no EN translation → Arabic fallback.
        $this->assertSame('عن المشروع — خاتم المرسلين', $page['seo_title']);
    }

    public function test_unknown_inactive_or_unpublished_pages_return_null(): void
    {
        $this->createPage('inactive', ['is_active' => false]);
        $this->createPage('future', ['published_at' => now()->addDay()]);

        $this->assertNull($this->service->getBySlug('missing'));
        $this->assertNull($this->service->getBySlug('inactive'));
        $this->assertNull($this->service->getBySlug('future'));
    }

    public function test_flush_invalidates_one_pages_cache(): void
    {
        $page = $this->createPage('terms');

        // Prime the cache.
        $this->assertSame('About the Project', $this->service->getBySlug('terms', 'en')['title']);

        $page->translations()->where('locale', 'en')->update(['title' => 'Updated Terms']);

        // Still cached until flushed.
        $this->assertSame('About the Project', $this->service->getBySlug('terms', 'en')['title']);

        $this->service->flush('terms');

        $this->assertSame('Updated Terms', $this->service->getBySlug('terms', 'en')['title']);
    }
}
