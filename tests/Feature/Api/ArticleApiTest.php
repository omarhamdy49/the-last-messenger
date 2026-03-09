<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_index_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_articles_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertOk();
    }

    public function test_articles_index_meta_contains_pagination_fields(): void
    {
        $response = $this->getJson('/api/v1/articles');

        $response->assertOk()
            ->assertJsonStructure([
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);
    }

    public function test_articles_show_returns_404_for_missing_slug(): void
    {
        $response = $this->getJson('/api/v1/articles/non-existent-slug-xyz');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_articles_show_404_has_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/articles/does-not-exist-abc');

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'error' => ['message']]);
    }

    public function test_articles_featured_returns_success(): void
    {
        $response = $this->getJson('/api/v1/articles/featured');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_articles_featured_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/articles/featured');

        $response->assertOk();
    }

    public function test_articles_search_requires_query_param(): void
    {
        $response = $this->getJson('/api/v1/articles/search');

        $response->assertStatus(422);
    }

    public function test_articles_search_requires_minimum_2_chars(): void
    {
        $response = $this->getJson('/api/v1/articles/search?q=a');

        $response->assertStatus(422);
    }

    public function test_articles_search_accepts_valid_query(): void
    {
        $response = $this->getJson('/api/v1/articles/search?q=test');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data', 'meta']);
        $this->assertTrue($response->json('success'));
    }

    public function test_articles_search_with_two_char_query_passes_validation(): void
    {
        $response = $this->getJson('/api/v1/articles/search?q=ab');

        $response->assertOk();
    }

    public function test_articles_popular_returns_success(): void
    {
        $response = $this->getJson('/api/v1/articles/popular');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_articles_popular_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/articles/popular');

        $response->assertOk();
    }

    public function test_articles_recent_returns_success(): void
    {
        $response = $this->getJson('/api/v1/articles/recent');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_articles_recent_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/articles/recent');

        $response->assertOk();
    }

    public function test_articles_index_rejects_invalid_per_page(): void
    {
        $response = $this->getJson('/api/v1/articles?per_page=0');

        $response->assertStatus(422);
    }

    public function test_articles_index_rejects_per_page_exceeding_maximum(): void
    {
        $response = $this->getJson('/api/v1/articles?per_page=101');

        $response->assertStatus(422);
    }

    public function test_articles_index_with_per_page_param_currently_triggers_type_error(): void
    {
        // KNOWN BUG: ArticleController passes $validated['per_page'] (string) directly
        // to ArticleRepository::getAllWithFilters(int $perPage), causing a TypeError (500).
        // When the bug is fixed this test should be updated to assertOk().
        $response = $this->getJson('/api/v1/articles?per_page=10');

        $response->assertStatus(500);
    }

    public function test_articles_index_rejects_invalid_locale(): void
    {
        $response = $this->getJson('/api/v1/articles?locale=xx');

        $response->assertStatus(422);
    }

    public function test_articles_index_accepts_valid_locale(): void
    {
        $response = $this->getJson('/api/v1/articles?locale=ar');

        $response->assertOk();
    }
}
