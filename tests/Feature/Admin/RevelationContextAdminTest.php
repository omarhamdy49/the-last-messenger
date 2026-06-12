<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\Models\RevelationContext;
use App\Domain\Quran\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RevelationContextAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_from_admin_index(): void
    {
        $this->get(route('admin.revelation-contexts.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_can_view_the_index_page(): void
    {
        $surah = $this->createSurah();
        $this->createContext($surah, 'حدث للاختبار');

        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.revelation-contexts.index'))
            ->assertOk()
            ->assertSee('حدث للاختبار');
    }

    public function test_admin_can_create_a_revelation_context_with_translations(): void
    {
        $surah = $this->createSurah();
        $this->actingAs($this->createAdminUser());

        $response = $this->post(route('admin.revelation-contexts.store'), [
            'surah_id' => $surah->id,
            'verse_from' => 1,
            'verse_to' => 3,
            'period' => 'makki',
            'event_key' => 'test-event',
            'display_order' => 7,
            'is_active' => '1',
            'title' => ['ar' => 'عنوان عربي', 'en' => 'English title'],
            'narrative' => ['ar' => 'سياق تاريخي عربي', 'en' => 'English narrative'],
            'source_reference' => ['ar' => 'صحيح البخاري', 'en' => 'Sahih al-Bukhari'],
        ]);

        $context = RevelationContext::query()->where('event_key', 'test-event')->firstOrFail();

        $response->assertRedirect(route('admin.revelation-contexts.show', $context->id));

        $this->assertSame('makki', $context->period);
        $this->assertSame(1, $context->verse_from);
        $this->assertSame(3, $context->verse_to);
        $this->assertSame(7, $context->display_order);
        $this->assertTrue($context->is_active);
        $this->assertSame('عنوان عربي', $context->getTranslation('title', 'ar'));
        $this->assertSame('English title', $context->getTranslation('title', 'en'));
        $this->assertSame('صحيح البخاري', $context->getTranslation('source_reference', 'ar'));
    }

    public function test_store_validation_returns_422_for_invalid_payload(): void
    {
        $surah = $this->createSurah();
        $this->actingAs($this->createAdminUser());

        $response = $this->postJson(route('admin.revelation-contexts.store'), [
            'surah_id' => $surah->id,
            'verse_from' => 5,
            'verse_to' => 2, // smaller than verse_from
            'period' => 'invalid-period',
            'title' => ['en' => 'Missing Arabic title'],
            'narrative' => ['en' => 'Missing Arabic narrative'],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['verse_to', 'period', 'title.ar', 'narrative.ar']);
    }

    public function test_admin_can_update_a_revelation_context(): void
    {
        $surah = $this->createSurah();
        $context = $this->createContext($surah, 'العنوان القديم');

        $this->actingAs($this->createAdminUser());

        $response = $this->put(route('admin.revelation-contexts.update', $context->id), [
            'surah_id' => $surah->id,
            'verse_from' => 2,
            'verse_to' => 4,
            'period' => 'madani',
            'display_order' => 9,
            'is_active' => '1',
            'title' => ['ar' => 'العنوان الجديد'],
            'narrative' => ['ar' => 'سياق محدث'],
        ]);

        $response->assertRedirect(route('admin.revelation-contexts.show', $context->id));

        $context->refresh()->load('translations');

        $this->assertSame('madani', $context->period);
        $this->assertSame(2, $context->verse_from);
        $this->assertSame('العنوان الجديد', $context->getTranslation('title', 'ar'));
    }

    public function test_admin_can_delete_a_revelation_context(): void
    {
        $surah = $this->createSurah();
        $context = $this->createContext($surah, 'سيُحذف');

        $this->actingAs($this->createAdminUser());

        $this->delete(route('admin.revelation-contexts.destroy', $context->id))
            ->assertRedirect(route('admin.revelation-contexts.index'));

        $this->assertDatabaseMissing('quran_revelation_contexts', ['id' => $context->id]);
        $this->assertDatabaseMissing('quran_revelation_context_translations', [
            'revelation_context_id' => $context->id,
        ]);
    }

    public function test_admin_can_view_show_create_and_edit_pages(): void
    {
        $surah = $this->createSurah();
        $context = $this->createContext($surah, 'حدث للعرض');

        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.revelation-contexts.show', $context->id))
            ->assertOk()
            ->assertSee('حدث للعرض');

        $this->get(route('admin.revelation-contexts.create'))->assertOk();

        $this->get(route('admin.revelation-contexts.edit', $context->id))
            ->assertOk()
            ->assertSee('حدث للعرض');
    }

    public function test_show_returns_404_for_missing_context(): void
    {
        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.revelation-contexts.show', 999999))
            ->assertNotFound();
    }

    private function createSurah(): Surah
    {
        return Surah::query()->create([
            'revelation_place' => 'makkah',
            'revelation_order' => 108,
            'bismillah_pre' => true,
            'name_ar' => 'الكوثر',
            'name_en' => 'Al-Kawthar',
            'transliteration' => 'Al-Kawthar',
            'verses_count' => 3,
        ]);
    }

    private function createContext(Surah $surah, string $arabicTitle): RevelationContext
    {
        /** @var RevelationContext $context */
        $context = RevelationContext::query()->create([
            'surah_id' => $surah->id,
            'verse_from' => 1,
            'verse_to' => null,
            'period' => 'makki',
            'display_order' => 1,
            'is_active' => true,
        ]);

        $context->translations()->create([
            'locale' => 'ar',
            'title' => $arabicTitle,
            'narrative' => 'سياق تاريخي للاختبار',
            'is_ready' => true,
        ]);

        return $context;
    }
}
