<?php

declare(strict_types=1);

namespace Tests\Feature\Content;

use App\Domain\Content\Enums\SiteSettingType;
use App\Domain\Content\Services\SiteSettingsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SiteSettingsServiceTest extends TestCase
{
    use RefreshDatabase;

    private SiteSettingsService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(SiteSettingsService::class);
    }

    public function test_set_and_get_translatable_setting_per_locale(): void
    {
        $this->service->set('site_name', [
            'ar' => 'خاتَمُ المُرْسَلِين',
            'en' => 'The Last Messenger',
        ], ['group' => 'general', 'type' => SiteSettingType::TEXT->value]);

        $this->assertSame('خاتَمُ المُرْسَلِين', $this->service->get('site_name', 'ar'));
        $this->assertSame('The Last Messenger', $this->service->get('site_name', 'en'));
    }

    public function test_translatable_setting_falls_back_to_arabic(): void
    {
        $this->service->set('site_name', ['ar' => 'خاتَمُ المُرْسَلِين'], ['group' => 'general']);

        $this->assertSame('خاتَمُ المُرْسَلِين', $this->service->get('site_name', 'fr'));
    }

    public function test_set_and_get_plain_setting(): void
    {
        $this->service->set('social_twitter', 'https://twitter.com/example', [
            'group' => 'social',
            'type' => SiteSettingType::URL->value,
        ]);

        $this->assertSame('https://twitter.com/example', $this->service->get('social_twitter'));
    }

    public function test_bool_setting_is_cast(): void
    {
        $this->service->set('maintenance_mode', true, ['group' => 'general', 'type' => SiteSettingType::BOOL->value]);

        $this->assertTrue($this->service->get('maintenance_mode'));

        $this->service->set('maintenance_mode', false, ['group' => 'general', 'type' => SiteSettingType::BOOL->value]);

        $this->assertFalse($this->service->get('maintenance_mode'));
    }

    public function test_write_flushes_the_cached_settings_map(): void
    {
        $this->service->set('contact_email', 'old@example.com', ['group' => 'contact']);

        // Prime the cache.
        $this->assertSame('old@example.com', $this->service->get('contact_email'));

        $this->service->set('contact_email', 'new@example.com', ['group' => 'contact']);

        $this->assertSame('new@example.com', $this->service->get('contact_email'));
    }

    public function test_unknown_key_returns_null(): void
    {
        $this->assertNull($this->service->get('does_not_exist'));
    }

    public function test_group_returns_only_that_groups_settings(): void
    {
        $this->service->set('social_twitter', '#', ['group' => 'social', 'type' => SiteSettingType::URL->value]);
        $this->service->set('contact_email', 'contact@example.com', ['group' => 'contact']);

        $social = $this->service->group('social');

        $this->assertCount(1, $social);
        $this->assertSame('social_twitter', $social->first()->key);
    }
}
