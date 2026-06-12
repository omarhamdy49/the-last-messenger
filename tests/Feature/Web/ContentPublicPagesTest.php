<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Content\Models\StaticPage;
use App\Domain\Content\Services\StaticPageService;
use Database\Seeders\FaqSeeder;
use Database\Seeders\HomeModuleCardsSeeder;
use Database\Seeders\MenuSeeder;
use Database\Seeders\QuickFilterChipsSeeder;
use Database\Seeders\SiteSettingsSeeder;
use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Covers the Content-domain wiring of the public site: DB-managed static
 * pages, the FAQ page, the home page module cards/chips, and the DB-managed
 * header/footer chrome — in Arabic (default) and English.
 */
final class ContentPublicPagesTest extends TestCase
{
    use RefreshDatabase;

    // ------------------------------------------------------------------
    // Static pages
    // ------------------------------------------------------------------

    public function test_about_page_renders_in_arabic(): void
    {
        $this->seed(StaticPagesSeeder::class);

        $response = $this->withSession(['locale' => 'ar'])->get('/pages/about');

        $response->assertOk();
        $response->assertSee('عن المشروع');
        $response->assertSee('رسالة المشروع');
    }

    public function test_about_page_renders_in_english(): void
    {
        $this->seed(StaticPagesSeeder::class);

        $response = $this->withSession(['locale' => 'en'])->get('/pages/about');

        $response->assertOk();
        $response->assertSee('About the Project');
    }

    public function test_about_alias_route_renders_the_same_page(): void
    {
        $this->seed(StaticPagesSeeder::class);

        $this->withSession(['locale' => 'ar'])->get('/about')->assertOk()->assertSee('عن المشروع');
        $this->withSession(['locale' => 'ar'])->get('/privacy')->assertOk();
        $this->withSession(['locale' => 'ar'])->get('/terms')->assertOk();
    }

    public function test_unknown_page_slug_returns_404(): void
    {
        $this->seed(StaticPagesSeeder::class);

        $this->withSession(['locale' => 'ar'])->get('/pages/does-not-exist')->assertNotFound();
    }

    public function test_unpublished_page_returns_404(): void
    {
        // Domain semantics: a page is hidden when inactive or future-dated
        // (published_at = null on an active page means "published now").
        $inactive = StaticPage::query()->create([
            'slug' => 'draft-page',
            'is_active' => false,
            'published_at' => null,
        ]);
        $inactive->translations()->create([
            'locale' => 'ar',
            'title' => 'مسودة',
            'body' => '<p>مسودة</p>',
            'is_ready' => true,
        ]);
        app(StaticPageService::class)->flush('draft-page');

        $this->withSession(['locale' => 'ar'])->get('/pages/draft-page')->assertNotFound();

        $scheduled = StaticPage::query()->create([
            'slug' => 'future-page',
            'is_active' => true,
            'published_at' => now()->addDay(),
        ]);
        $scheduled->translations()->create([
            'locale' => 'ar',
            'title' => 'قريباً',
            'body' => '<p>قريباً</p>',
            'is_ready' => true,
        ]);
        app(StaticPageService::class)->flush('future-page');

        $this->withSession(['locale' => 'ar'])->get('/pages/future-page')->assertNotFound();
    }

    // ------------------------------------------------------------------
    // FAQ
    // ------------------------------------------------------------------

    public function test_faq_page_renders_seeded_questions_grouped_by_category(): void
    {
        $this->seed(FaqSeeder::class);

        $response = $this->withSession(['locale' => 'ar'])->get('/faq');

        $response->assertOk();
        $response->assertSee('الأسئلة الشائعة');
        $response->assertSee('ما هو مشروع «خاتَمُ المُرْسَلِين»؟');
        // Category heading translated from the seeded category key
        $response->assertSee('عام');
    }

    public function test_faq_page_renders_empty_state_without_rows(): void
    {
        $response = $this->withSession(['locale' => 'ar'])->get('/faq');

        $response->assertOk();
        $response->assertSee('لم تُنشر أي أسئلة بعد.');
    }

    // ------------------------------------------------------------------
    // Home page — DB-managed cards, chips, and chrome
    // ------------------------------------------------------------------

    public function test_home_renders_seeded_module_cards_and_chips(): void
    {
        $this->seed([
            HomeModuleCardsSeeder::class,
            QuickFilterChipsSeeder::class,
            MenuSeeder::class,
            SiteSettingsSeeder::class,
        ]);

        $response = $this->withSession(['locale' => 'ar'])->get('/');

        $response->assertOk();
        // Seeded module card titles (Arabic, the default locale)
        $response->assertSee('السيرة النبوية');
        $response->assertSee('الحديث الشريف');
        $response->assertSee('القرآن الكريم');
        // Seeded quick-filter chip
        $response->assertSee('المعارك');
        // Fabricated metric must be gone
        $response->assertDontSee('3,542');
    }

    public function test_home_renders_db_managed_header_and_footer_chrome(): void
    {
        $this->seed([
            HomeModuleCardsSeeder::class,
            QuickFilterChipsSeeder::class,
            MenuSeeder::class,
            SiteSettingsSeeder::class,
        ]);

        $response = $this->withSession(['locale' => 'ar'])->get('/');

        $response->assertOk();
        // Header nav label from the seeded header menu
        $response->assertSee('المواضيع');
        // Footer quick link + copyright from settings
        $response->assertSee('سياسة الخصوصية');
        $response->assertSee('جميع الحقوق محفوظة');
    }

    public function test_home_renders_without_any_content_rows(): void
    {
        // Empty Content tables must never crash the page (graceful empty states)
        $this->withSession(['locale' => 'ar'])->get('/')->assertOk();
    }
}
