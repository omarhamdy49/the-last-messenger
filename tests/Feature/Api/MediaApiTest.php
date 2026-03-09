<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * MediaApiTest
 *
 * Tests for the stub MediaProgramController. All endpoints return a stub
 * response of { success: true, data: [], message: "Media programs coming soon" }
 * until the full domain model is implemented.
 */
class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    // ===================== PROGRAMS LIST =====================

    public function test_media_programs_index_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_index_returns_empty_data_array(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk()
            ->assertJsonPath('data', []);
    }

    public function test_media_programs_index_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk();
    }

    public function test_media_programs_index_has_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    // ===================== POPULAR =====================

    public function test_media_programs_popular_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/popular');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_popular_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/popular');

        $response->assertOk();
    }

    public function test_media_programs_popular_has_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/media/programs/popular');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
    }

    // ===================== RECENT =====================

    public function test_media_programs_recent_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/recent');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_recent_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/recent');

        $response->assertOk();
    }

    // ===================== SEARCH =====================

    public function test_media_programs_search_returns_success(): void
    {
        // Stub controller ignores all params and returns an empty success response.
        $response = $this->getJson('/api/v1/media/programs/search?q=test');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_search_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/search');

        $response->assertOk();
    }

    // ===================== SINGLE PROGRAM (slug) =====================

    public function test_media_programs_show_returns_success_for_any_slug(): void
    {
        // Stub controller returns success regardless of slug value.
        $response = $this->getJson('/api/v1/media/programs/some-program-slug');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_show_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/any-slug');

        $response->assertOk();
    }

    // ===================== EPISODES =====================

    public function test_media_programs_episodes_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/some-program-slug/episodes');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_episodes_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/some-slug/episodes');

        $response->assertOk();
    }

    public function test_media_programs_single_episode_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/some-slug/episodes/1');

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_media_programs_single_episode_does_not_require_authentication(): void
    {
        $response = $this->getJson('/api/v1/media/programs/any-slug/episodes/1');

        $response->assertOk();
    }
}
