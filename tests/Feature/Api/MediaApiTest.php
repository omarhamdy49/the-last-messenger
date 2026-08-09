<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\EpisodeWatchProgress;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MediaApiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
    }

    // ===================== HELPERS =====================

    /**
     * Create a published program with AR+EN translations.
     */
    private function createProgram(array $attributes = []): MediaProgram
    {
        /** @var MediaProgram $program */
        $program = MediaProgram::create(array_merge([
            'slug' => 'test-program-'.uniqid(),
            'category' => ProgramCategory::SEERAH,
            'maturity_rating' => 'all',
            'year' => 2020,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
            'display_order' => 1,
        ], $attributes));

        MediaProgramTranslation::create([
            'media_program_id' => $program->id,
            'locale' => 'ar',
            'title' => 'برنامج تجريبي',
            'description' => 'وصف البرنامج التجريبي',
            'host' => 'مقدم البرنامج',
            'is_ready' => true,
        ]);

        MediaProgramTranslation::create([
            'media_program_id' => $program->id,
            'locale' => 'en',
            'title' => 'Test Program',
            'description' => 'Test program description',
            'host' => 'Test Host',
            'is_ready' => true,
        ]);

        return $program;
    }

    /**
     * Create an episode belonging to a program.
     */
    private function createEpisode(MediaProgram $program, int $episodeNumber = 1, int $season = 1): MediaEpisode
    {
        /** @var MediaEpisode $episode */
        $episode = MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => $season,
            'episode_number' => $episodeNumber,
            'slug' => $program->slug.'-s'.$season.'e'.$episodeNumber,
            'video_url' => 'https://www.youtube.com/embed/test123',
            'duration_seconds' => 1800,
            'views_count' => 100,
            'is_featured' => false,
        ]);

        MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => 'حلقة رقم '.$episodeNumber,
            'description' => 'وصف الحلقة',
            'is_ready' => true,
        ]);

        MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'en',
            'title' => 'Episode '.$episodeNumber,
            'description' => 'Episode description',
            'is_ready' => true,
        ]);

        return $episode;
    }

    // ===================== PROGRAMS LIST =====================

    public function test_programs_index_returns_success_envelope(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_programs_index_is_available_to_authenticated_clients(): void
    {
        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk();
    }

    public function test_programs_index_returns_programs_list(): void
    {
        $this->createProgram(['slug' => 'list-program-a']);
        $this->createProgram(['slug' => 'list-program-b']);

        $response = $this->getJson('/api/v1/media/programs');

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    public function test_programs_index_rejects_per_page_above_cap(): void
    {
        $response = $this->getJson('/api/v1/media/programs?per_page=9999');

        $response->assertUnprocessable();
    }

    public function test_programs_index_filters_by_category(): void
    {
        $this->createProgram(['slug' => 'cat-seerah', 'category' => ProgramCategory::SEERAH]);
        $this->createProgram(['slug' => 'cat-quran', 'category' => ProgramCategory::QURAN]);

        $response = $this->getJson('/api/v1/media/programs?category=seerah');

        $response->assertOk();
        $this->assertTrue($response->json('success'));
    }

    // ===================== POPULAR =====================

    public function test_popular_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/popular');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_popular_is_available_to_authenticated_clients(): void
    {
        $response = $this->getJson('/api/v1/media/programs/popular');

        $response->assertOk();
    }

    // ===================== RECENT =====================

    public function test_recent_returns_success(): void
    {
        $response = $this->getJson('/api/v1/media/programs/recent');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    // ===================== SEARCH =====================

    public function test_search_returns_validation_error_when_query_too_short(): void
    {
        $response = $this->getJson('/api/v1/media/programs/search?q=a');

        // 1-character query should return a validation error response
        $response->assertStatus(422);
    }

    public function test_search_returns_results_with_valid_query(): void
    {
        $this->createProgram(['slug' => 'search-program-valid']);

        $response = $this->getJson('/api/v1/media/programs/search?q=test');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_search_is_available_to_authenticated_clients(): void
    {
        $response = $this->getJson('/api/v1/media/programs/search?q=test');

        $response->assertOk();
    }

    // ===================== SHOW (slug) =====================

    public function test_show_returns_200_for_existing_program(): void
    {
        $program = $this->createProgram(['slug' => 'show-program-exists']);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug);

        $response->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJsonPath('data.slug', $program->slug);
    }

    public function test_show_returns_404_for_unknown_slug(): void
    {
        $response = $this->getJson('/api/v1/media/programs/completely-unknown-slug-xyz');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_show_404_has_error_envelope(): void
    {
        $response = $this->getJson('/api/v1/media/programs/does-not-exist-abc');

        $response->assertNotFound()
            ->assertJsonStructure(['success', 'error' => ['message']]);
    }

    public function test_show_is_available_to_authenticated_clients(): void
    {
        $program = $this->createProgram(['slug' => 'show-no-auth']);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug);

        $response->assertOk();
    }

    // ===================== EPISODES LIST =====================

    public function test_episodes_list_returns_200_for_existing_program(): void
    {
        $program = $this->createProgram(['slug' => 'episodes-list-program']);
        $this->createEpisode($program, 1);
        $this->createEpisode($program, 2);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug.'/episodes');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data']);
        $this->assertTrue($response->json('success'));
    }

    public function test_episodes_list_returns_404_for_unknown_program(): void
    {
        $response = $this->getJson('/api/v1/media/programs/nonexistent-program-slug/episodes');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_episodes_list_is_available_to_authenticated_clients(): void
    {
        $program = $this->createProgram(['slug' => 'episodes-noauth']);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug.'/episodes');

        $response->assertOk();
    }

    // ===================== SINGLE EPISODE =====================

    public function test_single_episode_returns_200(): void
    {
        $program = $this->createProgram(['slug' => 'ep-show-program']);
        $episode = $this->createEpisode($program, 1);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug.'/episodes/1');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['episode']]);
    }

    public function test_single_episode_includes_next_episode_key(): void
    {
        $program = $this->createProgram(['slug' => 'ep-next-program']);
        $this->createEpisode($program, 1);
        $this->createEpisode($program, 2);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug.'/episodes/1');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data' => ['episode', 'next_episode']]);
    }

    public function test_single_episode_returns_404_for_unknown_program(): void
    {
        $response = $this->getJson('/api/v1/media/programs/no-program-here/episodes/1');

        $response->assertNotFound()
            ->assertJson(['success' => false]);
    }

    public function test_single_episode_is_available_to_authenticated_clients(): void
    {
        $program = $this->createProgram(['slug' => 'ep-noauth']);
        $this->createEpisode($program, 1);

        $response = $this->getJson('/api/v1/media/programs/'.$program->slug.'/episodes/1');

        $response->assertOk();
    }

    // ===================== WATCH PROGRESS =====================

    public function test_store_progress_requires_authentication(): void
    {
        auth('sanctum')->forgetUser();

        $program = $this->createProgram(['slug' => 'progress-auth-program']);
        $episode = $this->createEpisode($program, 1);

        $response = $this->postJson('/api/v1/media/progress', [
            'episode_id' => $episode->id,
            'seconds' => 120,
        ]);

        $response->assertUnauthorized();
    }

    public function test_store_progress_succeeds_for_authenticated_user(): void
    {
        $user = User::factory()->create();
        $program = $this->createProgram(['slug' => 'progress-ok-program']);
        $episode = $this->createEpisode($program, 1);

        Sanctum::actingAs($user, ['api:access']);
        $response = $this->postJson('/api/v1/media/progress', [
            'episode_id' => $episode->id,
            'seconds' => 300,
        ]);

        $response->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_store_progress_returns_422_for_invalid_data(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['api:access']);
        $response = $this->postJson('/api/v1/media/progress', [
            'episode_id' => 999999, // non-existent
            'seconds' => -1,     // invalid
        ]);

        $response->assertStatus(422);
    }

    public function test_store_progress_uses_authenticated_user_id_not_request_body(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $program = $this->createProgram(['slug' => 'progress-idor-program']);
        $episode = $this->createEpisode($program, 1);

        // Try to pass another user's ID in body — it must be ignored
        Sanctum::actingAs($user, ['api:access']);
        $this->postJson('/api/v1/media/progress', [
            'episode_id' => $episode->id,
            'seconds' => 500,
            'user_id' => $other->id, // IDOR attempt — must be silently ignored
        ]);

        $this->assertDatabaseHas('episode_watch_progress', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
        ]);

        $this->assertDatabaseMissing('episode_watch_progress', [
            'user_id' => $other->id,
            'episode_id' => $episode->id,
        ]);
    }

    public function test_store_progress_is_idempotent_upsert(): void
    {
        $user = User::factory()->create();
        $program = $this->createProgram(['slug' => 'progress-upsert-program']);
        $episode = $this->createEpisode($program, 1);

        // First request
        Sanctum::actingAs($user, ['api:access']);
        $this->postJson('/api/v1/media/progress', [
            'episode_id' => $episode->id,
            'seconds' => 100,
        ]);

        // Second request with updated seconds
        Sanctum::actingAs($user, ['api:access']);
        $this->postJson('/api/v1/media/progress', [
            'episode_id' => $episode->id,
            'seconds' => 500,
            'completed' => true,
        ]);

        $this->assertDatabaseCount('episode_watch_progress', 1);
        $this->assertDatabaseHas('episode_watch_progress', [
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'seconds' => 500,
            'completed' => true,
        ]);
    }

    // ===================== CONTINUE WATCHING =====================

    public function test_continue_watching_requires_authentication(): void
    {
        auth('sanctum')->forgetUser();

        $response = $this->getJson('/api/v1/media/continue-watching');

        $response->assertUnauthorized();
    }

    public function test_continue_watching_returns_success_for_authenticated_user(): void
    {
        $user = User::factory()->create();

        Sanctum::actingAs($user, ['api:access']);
        $response = $this->getJson('/api/v1/media/continue-watching');

        $response->assertOk()
            ->assertJsonStructure(['success', 'data'])
            ->assertJson(['success' => true]);
    }

    public function test_continue_watching_returns_only_incomplete_episodes(): void
    {
        $user = User::factory()->create();
        $program = $this->createProgram(['slug' => 'cw-program']);
        $ep1 = $this->createEpisode($program, 1);
        $ep2 = $this->createEpisode($program, 2);

        // ep1: in progress
        EpisodeWatchProgress::create([
            'user_id' => $user->id,
            'episode_id' => $ep1->id,
            'seconds' => 300,
            'completed' => false,
        ]);

        // ep2: completed — must NOT appear in continue-watching
        EpisodeWatchProgress::create([
            'user_id' => $user->id,
            'episode_id' => $ep2->id,
            'seconds' => 1800,
            'completed' => true,
        ]);

        Sanctum::actingAs($user, ['api:access']);
        $response = $this->getJson('/api/v1/media/continue-watching');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_continue_watching_returns_only_own_progress(): void
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        $program = $this->createProgram(['slug' => 'cw-own-program']);
        $episode = $this->createEpisode($program, 1);

        EpisodeWatchProgress::create([
            'user_id' => $user2->id,
            'episode_id' => $episode->id,
            'seconds' => 500,
            'completed' => false,
        ]);

        Sanctum::actingAs($user1, ['api:access']);
        $response = $this->getJson('/api/v1/media/continue-watching');

        $response->assertOk();
        $data = $response->json('data');
        $this->assertCount(0, $data);
    }
}
