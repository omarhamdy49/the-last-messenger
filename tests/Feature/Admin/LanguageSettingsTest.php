<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Seerah\Models\SeerahEvent;
use App\Domain\Shared\Models\Language;
use App\Domain\Shared\Services\LanguageCacheService;
use App\Domain\Shared\Services\LanguageService;
use App\Http\Resources\SeerahEventResource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LanguageSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_defaults_disable_french_and_urdu_without_removing_the_catalog(): void
    {
        $this->assertSame(['ar', 'en', 'tr'], enabled_content_locales());
        $this->assertSame(5, Language::count());
        $this->assertDatabaseHas('languages', ['code' => 'fr', 'is_active' => false]);
        $this->assertDatabaseHas('languages', ['code' => 'ur', 'is_active' => false]);
    }

    public function test_settings_toggle_persists_and_refreshes_every_language_consumer(): void
    {
        $this->actingAs($this->createAdminUser());
        $service = app(LanguageService::class);
        $cache = app(LanguageCacheService::class);
        $service->settings();
        $cache->getActiveLanguageCodes();
        $this->get(route('admin.settings.languages.index'))->assertOk()
            ->assertSee('/su/portal/v1/admin/settings/languages', false)
            ->assertSee('role="switch"', false);

        $this->postJson(route('admin.settings.languages.toggle', 'fr'))
            ->assertOk()->assertJsonPath('data.active', true);
        $this->assertContains('fr', Language::getActiveCodes());
        $this->assertContains('fr', $cache->getActiveLanguageCodes());
        $this->assertTrue($service->settings()['fr']['active']);
        Cache::flush();
        $this->assertContains('fr', enabled_content_locales());
        $service->applyRuntimeConfiguration();
        $this->assertArrayHasKey('fr', config('app.supported_locales'));
        $this->assertContains('fr', config('translatable.locales'));

        $this->postJson(route('admin.settings.languages.toggle', 'fr'))->assertOk()->assertJsonPath('data.active', false);
        Cache::flush();
        $this->assertNotContains('fr', enabled_content_locales());
        $service->applyRuntimeConfiguration();
        $this->assertArrayNotHasKey('fr', config('app.supported_locales'));
        $this->assertNotContains('fr', $cache->getActiveLanguageCodes());
    }

    public function test_default_and_required_arabic_are_protected_and_default_survives_cache_flush(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->postJson(route('admin.settings.languages.set-default', 'fr'))->assertUnprocessable();
        $this->postJson(route('admin.settings.languages.set-default', 'en'))->assertOk();
        $this->postJson(route('admin.settings.languages.set-default', 'en'))->assertOk();
        Cache::flush();
        $this->assertSame('en', Language::getDefaultCode());
        $this->assertSame(1, Language::where('is_default', true)->count());
        foreach (['ar', 'en'] as $code) {
            $this->postJson(route('admin.settings.languages.toggle', $code))->assertUnprocessable();
            $this->putJson(route('admin.settings.languages.update', $code), ['active' => false])->assertUnprocessable();
            $this->deleteJson(route('admin.settings.languages.destroy', $code))->assertUnprocessable();
        }
    }

    public function test_reordering_persists_and_cannot_drop_languages(): void
    {
        $this->actingAs($this->createAdminUser());
        $this->postJson(route('admin.settings.languages.reorder'), ['languages' => ['ar', 'en']])->assertUnprocessable();
        $this->postJson(route('admin.settings.languages.reorder'), ['languages' => ['tr', 'en', 'ar', 'ur', 'fr']])->assertOk();
        Cache::flush();
        $this->assertSame(['tr', 'en', 'ar'], enabled_content_locales());
        app(LanguageService::class)->seedDefaultLanguages();
        $this->assertSame(['tr', 'en', 'ar'], enabled_content_locales());
    }

    public function test_public_switch_and_selectors_only_allow_enabled_languages(): void
    {
        $this->postJson(route('language.switch'), ['language' => 'fr'])->assertUnprocessable();
        $this->postJson(route('language.switch'), ['language' => 'ur'])->assertUnprocessable();
        $this->withSession(['locale' => 'ur'])->get('/media?lang=fr')->assertOk()
            ->assertDontSee('value="fr"', false)->assertDontSee('value="ur"', false);
        $this->assertSame('ar', app()->getLocale());
        $this->postJson(route('language.switch'), ['language' => 'tr'])->assertOk();
        $this->assertSame('tr', session('locale'));
    }

    public function test_disabled_translations_are_hidden_preserved_during_edits_and_restored_when_enabled(): void
    {
        $this->actingAs($this->createAdminUser());
        $event = SeerahEvent::factory()->create();
        $event->translations()->where('locale', 'fr')->update(['title' => 'Titre conservé', 'content' => '<p>French preserved body</p>']);
        $event->translations()->where('locale', 'ur')->update(['content' => '<p>Urdu preserved body</p>']);
        $this->get(route('admin.seerah-events.edit', $event->id))->assertOk()
            ->assertSee('name="title[tr]"', false)
            ->assertDontSee('name="title[fr]"', false)->assertDontSee('name="content[ur]"', false);
        $this->get(route('admin.seerah-events.show', $event->id))->assertOk()
            ->assertDontSee('French preserved body')->assertDontSee('Urdu preserved body');
        $this->get(route('admin.seerah-milestones.create'))->assertOk()
            ->assertDontSee('name="title[fr]"', false)->assertDontSee('name="title[ur]"', false);
        $this->get(route('admin.articles.create'))->assertOk()
            ->assertDontSee('name="title[fr]', false)->assertDontSee('name="title[ur]', false);

        $this->put(route('admin.seerah-events.update', $event->id), [
            'title' => ['ar' => 'عنوان محدث', 'en' => 'Updated title'],
            'content' => ['ar' => '<p>محتوى محدث</p>'],
        ])->assertSessionHasNoErrors()->assertRedirect();
        $event->refresh();
        $this->assertSame('<p>French preserved body</p>', $event->getTranslation('content', 'fr', false));
        $this->assertSame('<p>Urdu preserved body</p>', $event->getTranslation('content', 'ur', false));
        $response = (new SeerahEventResource($event))->resolve();
        $this->assertArrayNotHasKey('fr', $response['title']);
        $this->assertArrayNotHasKey('ur', $response['title']);

        $this->postJson(route('admin.settings.languages.toggle', 'fr'))->assertOk();
        $this->get(route('admin.seerah-events.edit', $event->id))->assertOk()->assertSee('name="title[fr]"', false);
        $this->get(route('admin.seerah-events.show', $event->id))->assertOk()->assertSee('French preserved body');
    }

    public function test_missing_active_translation_does_not_fall_back_to_disabled_content(): void
    {
        $event = SeerahEvent::factory()->create();
        $event->translations()->whereIn('locale', ['ar', 'en', 'tr'])->update(['content' => null]);
        $this->assertNull($event->fresh()->getTranslation('content', 'ar'));
    }

    public function test_regular_users_cannot_change_language_settings(): void
    {
        $this->actingAs($this->createUser());
        $this->postJson(route('admin.settings.languages.toggle', 'fr'))->assertForbidden();
        $this->assertDatabaseHas('languages', ['code' => 'fr', 'is_active' => false]);
    }
}
