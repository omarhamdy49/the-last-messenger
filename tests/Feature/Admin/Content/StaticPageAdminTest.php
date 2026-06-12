<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\StaticPage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPageAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.content.static-pages.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_static_pages_index(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.content.static-pages.index'))
            ->assertOk()
            ->assertViewIs('admin.content.static-pages.index');
    }

    public function test_admin_can_create_static_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.content.static-pages.store'), [
            'slug' => 'about-us',
            'is_active' => '1',
            'published_at' => '2026-06-01 10:00',
            'title' => ['ar' => 'عن المشروع', 'en' => 'About the Project'],
            'body' => ['ar' => '<p>محتوى عربي</p>', 'en' => '<p>English body</p>'],
            'seo_title' => ['ar' => 'عن المشروع — خاتم المرسلين'],
            'seo_description' => ['ar' => 'وصف الصفحة'],
        ])->assertRedirect(route('admin.content.static-pages.index'));

        $page = StaticPage::query()->with('translations')->firstOrFail();

        $this->assertSame('about-us', $page->slug);
        $this->assertTrue($page->is_active);
        $this->assertSame('عن المشروع', $page->getTranslation('title', 'ar'));
        $this->assertSame('<p>English body</p>', $page->getTranslation('body', 'en'));
    }

    public function test_rich_body_is_sanitized_before_persisting(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.content.static-pages.store'), [
            'slug' => 'privacy',
            'is_active' => '1',
            'title' => ['ar' => 'الخصوصية'],
            'body' => [
                'ar' => '<p>نص آمن</p><script>alert("xss")</script>'
                    .'<p onclick="steal()">فقرة</p>'
                    .'<a href="javascript:alert(1)">رابط</a>'
                    .'<iframe src="https://evil.example"></iframe>',
            ],
        ])->assertRedirect(route('admin.content.static-pages.index'));

        $body = StaticPage::query()->with('translations')->firstOrFail()
            ->getTranslation('body', 'ar');

        $this->assertStringNotContainsString('<script', $body);
        $this->assertStringNotContainsString('onclick', $body);
        $this->assertStringNotContainsString('javascript:', $body);
        $this->assertStringNotContainsString('<iframe', $body);
        $this->assertStringContainsString('<p>نص آمن</p>', $body);
    }

    public function test_store_validation_fails_without_slug_and_arabic_title(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.content.static-pages.store'), [
            'slug' => '',
            'title' => ['en' => 'Missing Arabic'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug', 'title.ar']);
    }

    public function test_store_validation_rejects_duplicate_slug(): void
    {
        $this->actingAs($this->createAdminUser());

        StaticPage::query()->create(['slug' => 'about-us', 'is_active' => true]);

        $this->postJson(route('admin.content.static-pages.store'), [
            'slug' => 'about-us',
            'title' => ['ar' => 'عنوان'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['slug']);
    }

    public function test_admin_can_update_static_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $page = StaticPage::query()->create(['slug' => 'terms', 'is_active' => true]);
        $page->syncTranslations(['title' => ['ar' => 'الشروط']]);

        $this->put(route('admin.content.static-pages.update', $page->id), [
            'slug' => 'terms-of-use',
            'is_active' => '1',
            'title' => ['ar' => 'شروط الاستخدام'],
        ])->assertRedirect(route('admin.content.static-pages.index'));

        $page->refresh()->load('translations');

        $this->assertSame('terms-of-use', $page->slug);
        $this->assertSame('شروط الاستخدام', $page->getTranslation('title', 'ar'));
    }

    public function test_editing_missing_page_returns_404(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.content.static-pages.edit', 999999))->assertNotFound();
    }

    public function test_admin_can_delete_static_page(): void
    {
        $this->actingAs($this->createAdminUser());

        $page = StaticPage::query()->create(['slug' => 'old-page', 'is_active' => true]);

        $this->delete(route('admin.content.static-pages.destroy', $page->id))
            ->assertRedirect(route('admin.content.static-pages.index'));

        $this->assertDatabaseMissing('static_pages', ['id' => $page->id]);
    }
}
