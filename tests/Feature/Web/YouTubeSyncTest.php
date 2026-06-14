<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\DTOs\YouTubePlaylistData;
use App\Domain\Media\DTOs\YouTubePlaylistEntry;
use App\Domain\Media\Enums\MaturityRating;
use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Enums\YouTubeSyncStatus;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\YouTubeSyncRun;
use App\Domain\Media\Services\YouTubeSyncService;
use App\Domain\Media\Support\YouTubeClient;
use App\Domain\Media\Support\YouTubeUrl;
use App\Jobs\SyncYouTubePlaylistJob;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\Support\FakeYouTubeClient;
use Tests\TestCase;

/**
 * YouTube import/sync: admin endpoints (analyze, sync, status, episode sync)
 * and the playlist import run. The yt-dlp client is faked — no network.
 */
final class YouTubeSyncTest extends TestCase
{
    use RefreshDatabase;

    private const PLAYLIST_URL = 'https://www.youtube.com/playlist?list=PLtestPlaylist';

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->bindFakeClient();
    }

    private function bindFakeClient(): FakeYouTubeClient
    {
        $fake = new FakeYouTubeClient(playlist: new YouTubePlaylistData(
            id: 'PLtestPlaylist',
            title: 'برنامج تجريبي',
            description: 'وصف البرنامج التجريبي',
            channel: 'قناة الاختبار',
            thumbnailUrl: null,
            entries: [
                new YouTubePlaylistEntry('vidAAAAAA01', 'الحلقة الأولى', 1),
                new YouTubePlaylistEntry('vidAAAAAA02', 'الحلقة الثانية', 2),
            ],
        ));

        $this->app->instance(YouTubeClient::class, $fake);

        return $fake;
    }

    private function makeProgram(array $overrides = []): MediaProgram
    {
        return MediaProgram::create(array_merge([
            'slug' => 'prog-'.uniqid(),
            'category' => ProgramCategory::cases()[0]->value,
            'maturity_rating' => MaturityRating::cases()[0]->value,
            'year' => null,
            'total_seasons' => 1,
            'total_episodes' => 0,
            'rating' => 0,
            'is_featured' => false,
            'is_trending' => false,
            'is_published' => true,
            'display_order' => 0,
            'views_count' => 0,
        ], $overrides));
    }

    private function makeEpisode(MediaProgram $program, string $videoId): MediaEpisode
    {
        return MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'slug' => 'ep-'.uniqid(),
            'video_url' => YouTubeUrl::embedUrl($videoId),
            'duration_seconds' => 0,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => true,
        ]);
    }

    // ===================== ANALYZE =====================

    public function test_admin_can_analyze_a_playlist(): void
    {
        $program = $this->makeProgram();

        $this->actingAs($this->createAdminUser())
            ->postJson(route('admin.media-programs.youtube.analyze', $program->id), ['playlist_url' => self::PLAYLIST_URL])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 2)
            ->assertJsonPath('data.entries.0.title', 'الحلقة الأولى');
    }

    public function test_analyze_rejects_a_non_youtube_url(): void
    {
        $program = $this->makeProgram();

        $this->actingAs($this->createAdminUser())
            ->postJson(route('admin.media-programs.youtube.analyze', $program->id), ['playlist_url' => 'https://vimeo.com/123'])
            ->assertStatus(422)
            ->assertJsonValidationErrors('playlist_url');
    }

    public function test_non_admin_cannot_analyze(): void
    {
        $program = $this->makeProgram();

        $this->actingAs($this->createUser())
            ->post(route('admin.media-programs.youtube.analyze', $program->id), ['playlist_url' => self::PLAYLIST_URL])
            ->assertRedirect();
    }

    // ===================== SYNC (dispatch) =====================

    public function test_sync_creates_a_run_and_dispatches_the_job(): void
    {
        Queue::fake();
        $program = $this->makeProgram();

        $this->actingAs($this->createAdminUser())
            ->postJson(route('admin.media-programs.youtube.sync', $program->id), ['playlist_url' => self::PLAYLIST_URL])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['run_id', 'status_url']);

        $this->assertDatabaseHas('youtube_sync_runs', [
            'program_id' => $program->id,
            'status' => 'pending',
        ]);
        Queue::assertPushed(SyncYouTubePlaylistJob::class);
    }

    // ===================== STATUS =====================

    public function test_status_endpoint_reports_progress(): void
    {
        $program = $this->makeProgram();
        $run = YouTubeSyncRun::create([
            'program_id' => $program->id,
            'playlist_url' => self::PLAYLIST_URL,
            'status' => YouTubeSyncStatus::Running,
            'total' => 10,
            'processed' => 3,
            'created_count' => 2,
            'updated_count' => 1,
        ]);

        $this->actingAs($this->createAdminUser())
            ->getJson(route('admin.media.youtube.sync-status', $run->id))
            ->assertOk()
            ->assertJsonPath('data.processed', 3)
            ->assertJsonPath('data.percent', 30)
            ->assertJsonPath('data.is_terminal', false);
    }

    // ===================== PLAYLIST RUN (import) =====================

    public function test_process_run_imports_episodes_and_fills_program(): void
    {
        $program = $this->makeProgram();
        $run = YouTubeSyncRun::create([
            'program_id' => $program->id,
            'playlist_url' => self::PLAYLIST_URL,
            'status' => YouTubeSyncStatus::Pending,
        ]);

        app(YouTubeSyncService::class)->processRun($run->fresh());

        // Two episodes created with canonical embed URLs + fetched metadata.
        $episodes = MediaEpisode::where('program_id', $program->id)->orderBy('episode_number')->get();
        $this->assertCount(2, $episodes);
        $this->assertSame('https://www.youtube.com/embed/vidAAAAAA01', $episodes[0]->video_url);
        $this->assertSame(1800, $episodes[0]->duration_seconds);
        $this->assertSame('عنوان vidAAAAAA01', $episodes[0]->getTranslation('title', 'ar'));
        $this->assertSame('fake transcript text', $episodes[0]->getTranslation('transcript', 'ar'));

        // Program metadata filled from the playlist (was empty).
        $program->refresh();
        $this->assertSame('PLtestPlaylist', $program->youtube_playlist_id);
        $this->assertSame(2, $program->total_episodes);
        $this->assertSame('برنامج تجريبي', $program->getTranslation('title', 'ar'));

        // Run marked complete with correct counts.
        $run->refresh();
        $this->assertSame(YouTubeSyncStatus::Completed, $run->status);
        $this->assertSame(2, $run->created_count);
        $this->assertSame(2, $run->processed);
    }

    public function test_re_running_updates_in_place_without_duplicating(): void
    {
        $program = $this->makeProgram();

        foreach (['first', 'second'] as $pass) {
            $run = YouTubeSyncRun::create([
                'program_id' => $program->id,
                'playlist_url' => self::PLAYLIST_URL,
                'status' => YouTubeSyncStatus::Pending,
            ]);
            app(YouTubeSyncService::class)->processRun($run->fresh());
            $lastRun = $run->fresh();
        }

        $this->assertSame(2, MediaEpisode::where('program_id', $program->id)->count());
        $this->assertSame(0, $lastRun->created_count);
        $this->assertSame(2, $lastRun->updated_count);
    }

    // ===================== EPISODE SYNC =====================

    public function test_admin_can_sync_a_single_episode(): void
    {
        $program = $this->makeProgram();
        $episode = $this->makeEpisode($program, 'vidAAAAAA09');

        $this->actingAs($this->createAdminUser())
            ->post(route('admin.media-episodes.youtube.sync', $episode->id))
            ->assertRedirect(route('admin.media-episodes.show', $episode->id))
            ->assertSessionHas('success');

        $episode->refresh();
        $this->assertSame(1800, $episode->duration_seconds);
        $this->assertSame('عنوان vidAAAAAA09', $episode->getTranslation('title', 'ar'));
        $this->assertSame('https://www.youtube.com/embed/vidAAAAAA09', $episode->video_url);
    }

    public function test_episode_sync_fails_gracefully_without_a_youtube_url(): void
    {
        $program = $this->makeProgram();
        $episode = MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => 1,
            'slug' => 'ep-'.uniqid(),
            'video_url' => null,
            'duration_seconds' => 0,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => true,
        ]);

        $this->actingAs($this->createAdminUser())
            ->post(route('admin.media-episodes.youtube.sync', $episode->id))
            ->assertRedirect(route('admin.media-episodes.show', $episode->id))
            ->assertSessionHas('error');
    }
}
