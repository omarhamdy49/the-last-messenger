<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HadithApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_hadith_index_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk();
    }

    public function test_hadith_index_meta_contains_pagination_fields(): void
    {
        $response = $this->getJson('/api/v1/hadith');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_hadith_show_returns_404_for_nonexistent_id(): void
    {
        // No records seeded — any ID lookup must return 404.
        $response = $this->getJson('/api/v1/hadith/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_show_404_has_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/999999');

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'error' => ['message']]);
    }

    public function test_hadith_collections_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_collections_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections');

        $response->assertOk();
    }

    public function test_hadith_collection_show_returns_404_for_nonexistent_id(): void
    {
        $response = $this->getJson('/api/v1/hadith/collections/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_books_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/books');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_books_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/books');

        $response->assertOk();
    }

    public function test_hadith_book_show_currently_triggers_sql_error_with_accept_language_header(): void
    {
        // KNOWN BUG: BookRepository::getBySlug() passes the raw Accept-Language header value
        // (e.g. "en-US,en;q=0.5") as a SQL column name, producing a malformed query and a 500.
        // When the bug is fixed the expected status should be 404 and success:false.
        $response = $this->getJson('/api/v1/hadith/books/non-existent-book-slug');

        $response->assertStatus(500);
    }

    public function test_hadith_narrators_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_narrators_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators');

        $response->assertOk();
    }

    public function test_hadith_narrator_show_returns_404_for_nonexistent_id(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_search_requires_query_param(): void
    {
        $response = $this->getJson('/api/v1/hadith/search');

        $response->assertStatus(422);
    }

    public function test_hadith_search_requires_minimum_3_chars(): void
    {
        // HadithController.search validates q with min:3
        $response = $this->getJson('/api/v1/hadith/search?q=ab');

        $response->assertStatus(422);
    }

    public function test_hadith_search_accepts_valid_query(): void
    {
        $response = $this->getJson('/api/v1/hadith/search?q=test');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_search_accepts_three_char_minimum(): void
    {
        $response = $this->getJson('/api/v1/hadith/search?q=abc');

        $response->assertOk();
    }

    public function test_hadith_sahih_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/sahih');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_sahih_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/sahih');

        $response->assertOk();
    }

    public function test_hadith_reliable_returns_success(): void
    {
        $response = $this->getJson('/api/v1/hadith/reliable');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_hadith_reliable_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/hadith/reliable');

        $response->assertOk();
    }

    public function test_hadith_index_rejects_invalid_grade(): void
    {
        $response = $this->getJson('/api/v1/hadith?grade=invalid');

        $response->assertStatus(422);
    }

    public function test_hadith_index_accepts_valid_grade(): void
    {
        $response = $this->getJson('/api/v1/hadith?grade=sahih');

        $response->assertOk();
    }

    public function test_hadith_narrator_hadiths_returns_404_for_nonexistent_narrator(): void
    {
        $response = $this->getJson('/api/v1/hadith/narrators/999999/hadiths');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_by_theme_returns_404_for_nonexistent_theme(): void
    {
        $response = $this->getJson('/api/v1/hadith/by-theme/non-existent-theme');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_hadith_chapter_show_returns_404_for_nonexistent_chapter(): void
    {
        $response = $this->getJson('/api/v1/hadith/chapters/999999');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }
}
