<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Domain\Quran\Models\Recitation;
use App\Domain\Quran\Models\Reciter;
use App\Domain\Quran\Models\Surah;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class RecitationAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_is_redirected_to_login_from_store(): void
    {
        $reciter = $this->createReciter();
        $surah = $this->createSurah();

        $this->post(route('admin.reciters.recitations.store', $reciter->id), [
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/001.mp3',
            'format' => 'mp3',
        ])->assertRedirect(route('login'));
    }

    public function test_show_page_lists_surahs_with_and_without_audio(): void
    {
        $reciter = $this->createReciter();
        $surahWithAudio = $this->createSurah(108, 'الكوثر', 'Al-Kawthar');
        $surahWithoutAudio = $this->createSurah(1, 'الفاتحة', 'Al-Fatihah');

        Recitation::query()->create([
            'reciter_id' => $reciter->id,
            'surah_id' => $surahWithAudio->id,
            'audio_url' => 'https://cdn.example.com/108.mp3',
            'format' => 'mp3',
            'quality' => 'high',
        ]);

        $this->actingAs($this->createAdminUser());

        $this->get(route('admin.reciters.show', $reciter->id))
            ->assertOk()
            ->assertSee(__('quran.reciter_admin.list_title'));

        // Rows are loaded from the paginated endpoint after the page shell renders.
        $response = $this->getJson(route('admin.reciters.recitations.index', $reciter->id))
            ->assertOk()
            ->assertJsonPath('total', 2)
            ->assertJsonPath('with_audio', 1)
            ->assertJsonPath('missing', 1);

        $html = $response->json('html');
        $this->assertStringContainsString('الكوثر', $html);
        $this->assertStringContainsString('الفاتحة', $html);
        $this->assertStringContainsString(__('quran.recitations.has_audio'), $html);
        $this->assertStringContainsString(__('quran.recitations.no_audio'), $html);
    }

    public function test_admin_can_store_a_recitation(): void
    {
        $reciter = $this->createReciter();
        $surah = $this->createSurah();

        $this->actingAs($this->createAdminUser());

        $this->post(route('admin.reciters.recitations.store', $reciter->id), [
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/108.mp3',
            'format' => 'mp3',
            'quality' => 'high',
            'duration_seconds' => 90,
            'file_size_kb' => 1500,
        ])->assertRedirect(route('admin.reciters.show', $reciter->id));

        $this->assertDatabaseHas('quran_recitations', [
            'reciter_id' => $reciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/108.mp3',
            'format' => 'mp3',
            'quality' => 'high',
            'duration_seconds' => 90,
            'file_size_kb' => 1500,
        ]);
    }

    public function test_store_validation_returns_422_for_invalid_payload(): void
    {
        $reciter = $this->createReciter();
        $this->createSurah();

        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.reciters.recitations.store', $reciter->id), [
            'surah_id' => 999999,            // does not exist
            'audio_url' => 'not-a-url',      // invalid url
            'format' => 'wav',               // not in allowed list
            'quality' => 'ultra',            // not in allowed list
            'duration_seconds' => -5,        // negative
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['surah_id', 'audio_url', 'format', 'quality', 'duration_seconds']);
    }

    public function test_store_rejects_duplicate_surah_format_for_same_reciter(): void
    {
        $reciter = $this->createReciter();
        $surah = $this->createSurah();

        Recitation::query()->create([
            'reciter_id' => $reciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/first.mp3',
            'format' => 'mp3',
        ]);

        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.reciters.recitations.store', $reciter->id), [
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/second.mp3',
            'format' => 'mp3',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['surah_id']);
    }

    public function test_admin_can_update_a_recitation(): void
    {
        $reciter = $this->createReciter();
        $surah = $this->createSurah();

        $recitation = Recitation::query()->create([
            'reciter_id' => $reciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/old.mp3',
            'format' => 'mp3',
            'quality' => 'low',
        ]);

        $this->actingAs($this->createAdminUser());

        $this->put(
            route('admin.reciters.recitations.update', [$reciter->id, $recitation->id]),
            [
                'surah_id' => $surah->id,
                'audio_url' => 'https://cdn.example.com/new.aac',
                'format' => 'aac',
                'quality' => 'high',
            ]
        )->assertRedirect(route('admin.reciters.show', $reciter->id));

        $this->assertDatabaseHas('quran_recitations', [
            'id' => $recitation->id,
            'audio_url' => 'https://cdn.example.com/new.aac',
            'format' => 'aac',
            'quality' => 'high',
        ]);
    }

    public function test_update_returns_404_when_recitation_belongs_to_another_reciter(): void
    {
        $reciter = $this->createReciter();
        $otherReciter = $this->createReciter('other-qari');
        $surah = $this->createSurah();

        $recitation = Recitation::query()->create([
            'reciter_id' => $otherReciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/old.mp3',
            'format' => 'mp3',
        ]);

        $this->actingAs($this->createAdminUser());

        // Nested under the wrong reciter -> ownership check fails -> 404
        $this->putJson(
            route('admin.reciters.recitations.update', [$reciter->id, $recitation->id]),
            [
                'surah_id' => $surah->id,
                'audio_url' => 'https://cdn.example.com/new.mp3',
                'format' => 'mp3',
            ]
        )->assertStatus(404);
    }

    public function test_admin_can_delete_a_recitation(): void
    {
        $reciter = $this->createReciter();
        $surah = $this->createSurah();

        $recitation = Recitation::query()->create([
            'reciter_id' => $reciter->id,
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/108.mp3',
            'format' => 'mp3',
        ]);

        $this->actingAs($this->createAdminUser());

        $this->delete(route('admin.reciters.recitations.destroy', [$reciter->id, $recitation->id]))
            ->assertRedirect(route('admin.reciters.show', $reciter->id));

        $this->assertDatabaseMissing('quran_recitations', ['id' => $recitation->id]);
    }

    public function test_store_returns_404_for_missing_reciter(): void
    {
        $surah = $this->createSurah();

        $this->actingAs($this->createAdminUser());

        $this->postJson(route('admin.reciters.recitations.store', 999999), [
            'surah_id' => $surah->id,
            'audio_url' => 'https://cdn.example.com/108.mp3',
            'format' => 'mp3',
        ])->assertStatus(422)
            ->assertJsonValidationErrors(['reciter_id']);
    }

    private function createReciter(string $slug = 'famous-qari'): Reciter
    {
        /** @var Reciter $reciter */
        $reciter = Reciter::query()->create([
            'slug' => $slug,
            'style' => 'Tajweed',
            'is_featured' => false,
            'is_active' => true,
            'display_order' => 1,
        ]);

        $reciter->translations()->create([
            'locale' => 'ar',
            'name' => 'قارئ للاختبار',
            'is_ready' => true,
        ]);

        return $reciter;
    }

    private function createSurah(int $order = 108, string $nameAr = 'الكوثر', string $nameEn = 'Al-Kawthar'): Surah
    {
        return Surah::query()->create([
            'revelation_place' => 'makkah',
            'revelation_order' => $order,
            'bismillah_pre' => true,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'transliteration' => $nameEn,
            'verses_count' => 3,
        ]);
    }
}
