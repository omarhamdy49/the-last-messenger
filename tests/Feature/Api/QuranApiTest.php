<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
