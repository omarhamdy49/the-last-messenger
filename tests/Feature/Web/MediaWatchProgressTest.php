<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

/**
 * Covers the session-auth watch-progress write path (POST /media/progress),
 * the resume position on the episode page, and the per-episode progress bars
 * on the program page.
 */
final class MediaWatchProgressTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Page renders dispatch RecordMediaViewJob (sync queue in tests) —
        // keep it out of these assertions.
        Queue::fake();
    }

    /**
     * Insert a minimal published program (+ Arabic translation), return its id.
     */
    private function makeProgram(string $slug): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => false,
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_program_translations')->insert([
            'media_program_id' => $programId,
            'locale' => 'ar',
            'title' => 'برنامج الاختبار',
            'description' => 'وصف تجريبي',
            'host' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    /**
     * Insert a published episode for the program, return its id.
     */
    private function makeEpisode(int $programId, int $number, bool $published = true, int $duration = 600): int
    {
        $episodeId = (int) DB::table('media_episodes')->insertGetId([
            'program_id' => $programId,
            'season_number' => 1,
            'episode_number' => $number,
            'slug' => "test-episode-{$programId}-{$number}",
            'video_url' => 'https://www.youtube.com/embed/dQw4w9WgXcQ',
            'duration_seconds' => $duration,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => $published,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_episode_translations')->insert([
            'media_episode_id' => $episodeId,
            'locale' => 'ar',
            'title' => "الحلقة {$number}",
            'description' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $episodeId;
    }

    public function test_guest_progress_post_is_unauthorized(): void
    {
        $episodeId = $this->makeEpisode($this->makeProgram('guest-progress'), 1);

        $this->postJson(route('public.media.progress'), [
            'episode_id' => $episodeId,
            'seconds' => 30,
        ])->assertUnauthorized();

        $this->assertSame(0, DB::table('episode_watch_progress')->count());
    }

    public function test_auth_progress_post_creates_then_upserts_a_single_row(): void
    {
        $user = User::factory()->create();
        $episodeId = $this->makeEpisode($this->makeProgram('upsert-progress'), 1);

        $this->actingAs($user)
            ->postJson(route('public.media.progress'), ['episode_id' => $episodeId, 'seconds' => 30])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('episode_watch_progress', [
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 30,
            'completed' => false,
        ]);

        $this->actingAs($user)
            ->postJson(route('public.media.progress'), ['episode_id' => $episodeId, 'seconds' => 90, 'completed' => true])
            ->assertOk();

        $this->assertSame(1, DB::table('episode_watch_progress')->where('user_id', $user->id)->count());
        $this->assertDatabaseHas('episode_watch_progress', [
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 90,
            'completed' => true,
        ]);
    }

    public function test_progress_post_validates_episode_and_seconds(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->postJson(route('public.media.progress'), ['episode_id' => 999999, 'seconds' => 10])
            ->assertStatus(422)
            ->assertJsonValidationErrors('episode_id');

        $episodeId = $this->makeEpisode($this->makeProgram('validate-progress'), 1);

        $this->actingAs($user)
            ->postJson(route('public.media.progress'), ['episode_id' => $episodeId, 'seconds' => -5])
            ->assertStatus(422)
            ->assertJsonValidationErrors('seconds');
    }

    public function test_progress_post_rejects_unpublished_episode_with_404(): void
    {
        $user = User::factory()->create();
        $episodeId = $this->makeEpisode($this->makeProgram('draft-progress'), 1, published: false);

        $this->actingAs($user)
            ->postJson(route('public.media.progress'), ['episode_id' => $episodeId, 'seconds' => 10])
            ->assertNotFound();

        $this->assertSame(0, DB::table('episode_watch_progress')->count());
    }

    public function test_episode_page_exposes_resume_seconds_for_saved_progress(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeProgram('resume-program');
        $episodeId = $this->makeEpisode($programId, 1);

        DB::table('episode_watch_progress')->insert([
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 120,
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('public.media.episode', ['slug' => 'resume-program', 'episode' => 1]))
            ->assertOk()
            ->assertViewHas('resumeSeconds', 120);
    }

    public function test_completed_episode_restarts_instead_of_resuming(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeProgram('restart-program');
        $episodeId = $this->makeEpisode($programId, 1);

        DB::table('episode_watch_progress')->insert([
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 600,
            'completed' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('public.media.episode', ['slug' => 'restart-program', 'episode' => 1]))
            ->assertOk()
            ->assertViewHas('resumeSeconds', 0);
    }

    public function test_program_page_renders_progress_percent_for_auth_user_only(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeProgram('bars-program');
        $episodeId = $this->makeEpisode($programId, 1, duration: 200);

        DB::table('episode_watch_progress')->insert([
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 100,
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Guest first — actingAs() persists for the rest of the test.
        $this->get(route('public.media.program', 'bars-program'))
            ->assertOk()
            ->assertViewHas('episodes', function (array $episodes): bool {
                return ! array_key_exists('progress_percent', $episodes[0]);
            });

        $this->actingAs($user)
            ->get(route('public.media.program', 'bars-program'))
            ->assertOk()
            ->assertViewHas('episodes', function (array $episodes): bool {
                return ($episodes[0]['progress_percent'] ?? null) === 50;
            });
    }

    public function test_completed_rows_stay_out_of_continue_watching(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeProgram('cw-program');
        $inProgressId = $this->makeEpisode($programId, 1);
        $completedId = $this->makeEpisode($programId, 2);

        DB::table('episode_watch_progress')->insert([
            [
                'user_id' => $user->id,
                'episode_id' => $inProgressId,
                'seconds' => 60,
                'completed' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'user_id' => $user->id,
                'episode_id' => $completedId,
                'seconds' => 600,
                'completed' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->actingAs($user)
            ->getJson(route('public.media.continue-watching'))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $inProgressId);
    }

    public function test_continue_watching_endpoint_rejects_guests(): void
    {
        $this->getJson(route('public.media.continue-watching'))->assertUnauthorized();
    }

    public function test_media_page_carries_no_per_user_continue_watching_markup(): void
    {
        $user = User::factory()->create();
        $programId = $this->makeProgram('lazy-program');
        $episodeId = $this->makeEpisode($programId, 1);

        DB::table('episode_watch_progress')->insert([
            'user_id' => $user->id,
            'episode_id' => $episodeId,
            'seconds' => 60,
            'completed' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // The hub page renders the lazy-load shell, never the user's episodes.
        $this->actingAs($user)
            ->get(route('public.media'))
            ->assertOk()
            ->assertDontSee('lazy-program/episodes');
    }
}
