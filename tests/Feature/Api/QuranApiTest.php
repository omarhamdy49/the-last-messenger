<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * QuranApiTest
 *
 * Covers search validation, random verse, and other routes not addressed by
 * the existing QuranTest.php (which focuses on surah serialization caveats).
 * These tests run against an empty database, so all data-fetching endpoints
 * that depend on seeded records will return 404 — this is the correct
 * documented behaviour for those paths.
 */
class QuranApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Surah lookups are cached and the 'search' rate limiter is cache-backed;
        // flushing per test keeps cross-test state out of navigational search.
        Cache::flush();
    }

    // ===================== SURAH ENDPOINTS =====================

    public function test_surahs_index_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/quran/surahs');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_surahs_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/quran/surahs');

        $response->assertOk();
    }

    public function test_single_surah_returns_404_for_out_of_range_number(): void
    {
        // 200 is outside the valid 1–114 range; the service maps this to 404.
        $response = $this->getJson('/api/v1/quran/surahs/200');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_single_surah_returns_404_when_no_data_seeded(): void
    {
        // Valid surah number but no records in the DB → service throws → 404.
        $response = $this->getJson('/api/v1/quran/surahs/1');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    // ===================== VERSE ENDPOINTS =====================

    public function test_verses_for_surah_returns_404_when_surah_out_of_range(): void
    {
        // 999 exceeds the 1–114 range.
        $response = $this->getJson('/api/v1/quran/surahs/999/verses');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_verses_for_surah_returns_404_when_no_data_seeded(): void
    {
        $response = $this->getJson('/api/v1/quran/surahs/1/verses');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_random_verse_returns_404_when_no_data_seeded(): void
    {
        // getRandomVerse() returns null on an empty DB and the controller maps it to 404.
        $response = $this->getJson('/api/v1/quran/random');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_random_verse_route_is_public_not_protected(): void
    {
        // The route must never return 401; on an empty DB it 404s, but
        // authorization must not be the cause of failure.
        $response = $this->getJson('/api/v1/quran/random');

        $response->assertNotFound();
    }

    // ===================== SEARCH ENDPOINTS =====================

    public function test_quran_search_requires_query_param(): void
    {
        $response = $this->getJson('/api/v1/quran/search');

        // QuranController.search catches ValidationException and returns
        // a 422 via validationErrorResponse().
        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quran_search_requires_minimum_2_chars(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=a');

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quran_search_accepts_valid_query(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test');

        // Empty DB means 0 results, but the response envelope must be present.
        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_quran_search_accepts_two_char_minimum(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=ab');

        $response->assertOk();
    }

    public function test_quran_search_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test');

        $response->assertOk();
    }

    public function test_quran_search_rejects_invalid_lang(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test&lang=xx');

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quran_search_accepts_valid_lang(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test&lang=ar');

        $response->assertOk();
    }

    public function test_quran_search_rejects_invalid_type(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test&type=unknown');

        $response->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_quran_search_accepts_arabic_type(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test&type=arabic');

        $response->assertOk();
    }

    public function test_quran_search_accepts_translation_type(): void
    {
        $response = $this->getJson('/api/v1/quran/search?q=test&type=translation');

        $response->assertOk();
    }

    // ===================== NAVIGATIONAL SEARCH (suggest) =====================

    public function test_suggest_requires_query_param(): void
    {
        $this->getJson('/api/v1/quran/search/suggest')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_suggest_rejects_overlong_query(): void
    {
        $this->getJson('/api/v1/quran/search/suggest?q='.str_repeat('a', 101))
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_suggest_does_not_require_authentication(): void
    {
        $this->getJson('/api/v1/quran/search/suggest?q=2:255')->assertOk();
    }

    public function test_suggest_resolves_verse_key(): void
    {
        $response = $this->getJson('/api/v1/quran/search/suggest?q=2:255')->assertOk();

        $this->assertSame('verse', $response->json('data.0.type'));
        $this->assertSame('2:255', $response->json('data.0.verse_key'));
        $this->assertSame(2, $response->json('data.0.surah_number'));
        $this->assertSame(255, $response->json('data.0.verse_number'));
    }

    public function test_suggest_resolves_juz(): void
    {
        $response = $this->getJson('/api/v1/quran/search/suggest?q=juz 5')->assertOk();

        $this->assertSame('juz', $response->json('data.0.type'));
        $this->assertSame(5, $response->json('data.0.number'));
    }

    public function test_suggest_resolves_page(): void
    {
        $response = $this->getJson('/api/v1/quran/search/suggest?q=page 50')->assertOk();

        $this->assertSame('page', $response->json('data.0.type'));
        $this->assertSame(50, $response->json('data.0.number'));
    }

    public function test_suggest_resolves_bare_surah_number(): void
    {
        $response = $this->getJson('/api/v1/quran/search/suggest?q=5')->assertOk();

        $this->assertSame('surah', $response->json('data.0.type'));
        $this->assertSame(5, $response->json('data.0.surah_number'));
    }

    public function test_suggest_normalizes_arabic_indic_digits(): void
    {
        // ٢:٢٥٥ is the Arabic-Indic spelling of 2:255.
        $response = $this->getJson('/api/v1/quran/search/suggest?q='.urlencode('٢:٢٥٥'))->assertOk();

        $this->assertSame('verse', $response->json('data.0.type'));
        $this->assertSame('2:255', $response->json('data.0.verse_key'));
    }

    public function test_suggest_returns_empty_for_out_of_range_division(): void
    {
        // Only 30 juz exist.
        $response = $this->getJson('/api/v1/quran/search/suggest?q=juz 99')->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    public function test_suggest_matches_surah_by_name(): void
    {
        $this->seedSurah(2, 'البقرة', 'Al-Baqarah', 286);

        $response = $this->getJson('/api/v1/quran/search/suggest?q=Baqarah')->assertOk();

        $surah = collect($response->json('data'))->firstWhere('type', 'surah');
        $this->assertNotNull($surah);
        $this->assertSame(2, $surah['surah_number']);
    }

    public function test_suggest_rejects_verse_number_beyond_surah_length(): void
    {
        // Al-Fatihah has 7 verses, so 1:8 must not resolve.
        $this->seedSurah(1, 'الفاتحة', 'Al-Fatihah', 7);

        $response = $this->getJson('/api/v1/quran/search/suggest?q=1:8')->assertOk();

        $this->assertSame([], $response->json('data'));
    }

    public function test_suggest_label_respects_locale_param(): void
    {
        $en = $this->getJson('/api/v1/quran/search/suggest?q=2:255&locale=en')->assertOk();
        $this->assertStringContainsString('Verse', (string) $en->json('data.0.label'));

        $ar = $this->getJson('/api/v1/quran/search/suggest?q=2:255&locale=ar')->assertOk();
        $this->assertStringContainsString('آية', (string) $ar->json('data.0.label'));
    }

    public function test_suggest_rejects_invalid_locale(): void
    {
        $this->getJson('/api/v1/quran/search/suggest?q=2:255&locale=xx')
            ->assertStatus(422)
            ->assertJson(['success' => false]);
    }

    public function test_suggest_does_not_match_single_letter_surah_loosely(): void
    {
        // "ق" (Surah Qaf) must not match a query just because it contains that letter.
        $this->seedSurah(50, 'ق', 'Qaf', 45);

        $response = $this->getJson('/api/v1/quran/search/suggest?q='.urlencode('بقرة').'&locale=ar')->assertOk();

        $numbers = collect($response->json('data'))->pluck('surah_number');
        $this->assertFalse($numbers->contains(50));
    }

    private function seedSurah(int $number, string $nameAr, string $nameEn, int $versesCount): void
    {
        DB::table('quran_surahs')->insert([
            'revelation_order' => $number,
            'revelation_place' => 'makkah',
            'bismillah_pre' => true,
            'name_ar' => $nameAr,
            'name_en' => $nameEn,
            'transliteration' => $nameEn,
            'verses_count' => $versesCount,
            'pages' => json_encode([1]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
