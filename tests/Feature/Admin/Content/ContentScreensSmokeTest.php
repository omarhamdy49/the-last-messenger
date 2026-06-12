<?php

declare(strict_types=1);

namespace Tests\Feature\Admin\Content;

use App\Domain\Content\Models\SiteSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Renders every Content admin screen as an admin so Blade/runtime errors
 * surface in CI, and covers the settings save round-trip.
 */
class ContentScreensSmokeTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_content_index_screen_renders_for_admin(): void
    {
        $this->actingAs($this->createAdminUser());

        $screens = [
            route('admin.content.settings.index'),
            route('admin.content.menus.index'),
            route('admin.content.menus.create'),
            route('admin.content.home-cards.index'),
            route('admin.content.quick-filters.index'),
            route('admin.content.static-pages.index'),
            route('admin.content.faqs.index'),
            route('admin.content.contact-messages.index'),
            route('admin.content.imports.index'),
            route('admin.content.imports.logs'),
        ];

        foreach ($screens as $url) {
            $this->get($url)->assertOk();
        }
    }

    public function test_rich_text_form_screens_render_for_admin(): void
    {
        // The rich-text component loads its Quill bundle via @vite;
        // fake the manifest so tests do not depend on a prior npm build.
        $this->withoutVite();
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.content.static-pages.create'))->assertOk();
        $this->get(route('admin.content.faqs.create'))->assertOk();
        $this->get(route('admin.content.home-cards.create'))->assertOk();
        $this->get(route('admin.content.quick-filters.create'))->assertOk();
    }

    public function test_admin_can_save_settings_group_with_sanitized_richtext(): void
    {
        $this->actingAs($this->createAdminUser());

        SiteSetting::query()->create([
            'key' => 'site_name', 'group' => 'general', 'type' => 'text',
            'is_translatable' => true, 'sort_order' => 0,
        ]);
        SiteSetting::query()->create([
            'key' => 'footer_about', 'group' => 'general', 'type' => 'richtext',
            'is_translatable' => true, 'sort_order' => 1,
        ]);
        SiteSetting::query()->create([
            'key' => 'maintenance_mode', 'group' => 'general', 'type' => 'bool',
            'is_translatable' => false, 'sort_order' => 2, 'value' => '0',
        ]);

        $this->put(route('admin.content.settings.update', ['group' => 'general']), [
            'settings' => [
                'site_name' => ['ar' => 'خاتم المرسلين', 'en' => 'The Last Messenger'],
                'footer_about' => ['ar' => '<p>نبذة</p><script>alert(1)</script>'],
                'maintenance_mode' => '1',
            ],
        ])->assertRedirect(route('admin.content.settings.index', ['group' => 'general']));

        $service = app(\App\Domain\Content\Services\SiteSettingsService::class);

        $this->assertSame('خاتم المرسلين', $service->get('site_name', 'ar'));
        $this->assertSame('The Last Messenger', $service->get('site_name', 'en'));
        $this->assertTrue($service->get('maintenance_mode'));
        $this->assertStringNotContainsString('<script', (string) $service->get('footer_about', 'ar'));
        $this->assertStringContainsString('<p>نبذة</p>', (string) $service->get('footer_about', 'ar'));
    }

    public function test_settings_save_rejects_invalid_payload(): void
    {
        $this->actingAs($this->createAdminUser());

        SiteSetting::query()->create([
            'key' => 'twitter_url', 'group' => 'social', 'type' => 'url',
            'is_translatable' => false, 'sort_order' => 0,
        ]);

        $this->putJson(route('admin.content.settings.update', ['group' => 'social']), [
            'settings' => ['twitter_url' => 'not-a-url'],
        ])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['settings.twitter_url']);
    }
}
