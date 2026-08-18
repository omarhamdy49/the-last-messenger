<?php

declare(strict_types=1);

namespace Tests\Feature\Web;

use App\Domain\Media\Enums\ProgramCategory;
use App\Domain\Media\Models\EpisodeWatchProgress;
use App\Domain\Media\Models\MediaCategory;
use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Tests\Support\CommitsForFullTextSearch;
use Tests\TestCase;

/**
 * Public media pages: programs hub, program detail, episode player,
 * and unified-search inclusion of media programs.
 */
final class MediaPublicPagesTest extends TestCase
{
    /**
     * DatabaseTruncation, NOT RefreshDatabase — required, not a preference.
     *
     * Unified search now uses MySQL FULLTEXT (MATCH…AGAINST) instead of a full-table LIKE.
     * InnoDB does not populate a FULLTEXT index until the inserting transaction COMMITS, and
     * RefreshDatabase wraps each test in a transaction it rolls back — so MATCH against rows
     * the test just seeded matches nothing. The query is valid and silently returns zero
     * rows, which reads as a search bug rather than a test-isolation artefact.
     */
    use CommitsForFullTextSearch;

    private function createProgram(array $overrides = [], string $title = 'برنامج السيرة التجريبي'): MediaProgram
    {
        $program = MediaProgram::create(array_merge([
            'slug' => 'test-program-'.uniqid(),
            'category' => ProgramCategory::SEERAH,
            'maturity_rating' => 'all',
            'year' => 2020,
            'rating' => 8.5,
            'is_featured' => true,
            'is_trending' => true,
            'views_count' => 0,
            'display_order' => 1,
        ], $overrides));

        foreach (['ar', 'en'] as $locale) {
            MediaProgramTranslation::create([
                'media_program_id' => $program->id,
                'locale' => $locale,
                'title' => $title,
                'description' => 'وصف البرنامج التجريبي للاختبار',
                'host' => 'مقدم البرنامج',
                'is_ready' => true,
            ]);
        }

        return $program;
    }

    private function createEpisode(MediaProgram $program, int $number, ?string $videoUrl, string $title): MediaEpisode
    {
        $episode = MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => 1,
            'episode_number' => $number,
            'slug' => $program->slug.'-e'.$number,
            'video_url' => $videoUrl,
            'duration_seconds' => 1800,
            'views_count' => 0,
            'is_featured' => false,
        ]);

        foreach (['ar', 'en'] as $locale) {
            MediaEpisodeTranslation::create([
                'media_episode_id' => $episode->id,
                'locale' => $locale,
                'title' => $title,
                'description' => 'وصف الحلقة التجريبية',
                'is_ready' => true,
            ]);
        }

        return $episode;
    }

    // ===================== HUB =====================

    public function test_hub_returns_200_with_seeded_programs(): void
    {
        $program = $this->createProgram(title: 'برنامج الاختبار المميز');
        $this->createEpisode($program, 1, 'https://www.youtube.com/embed/cu68afTJVlk', 'الحلقة الأولى');

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee('برنامج الاختبار المميز');
        $response->assertSee(trans('media.public.trending_now'));
    }

    public function test_hub_returns_200_with_empty_state_when_no_programs(): void
    {
        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.no_programs_title'));
    }

    public function test_hub_hides_category_row_when_category_is_unfeatured(): void
    {
        // Program appears ONLY in its category row (not featured/trending/popular).
        $this->createProgram(
            ['is_featured' => false, 'is_trending' => false],
            title: 'برنامج فئة مخفية'
        );

        MediaCategory::create(['key' => 'seerah', 'is_featured' => false, 'display_order' => 0]);
        MediaCategory::create(['key' => 'quran', 'is_featured' => true, 'display_order' => 1]);

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertDontSee('برنامج فئة مخفية');
    }

    public function test_hub_shows_category_row_when_category_is_featured(): void
    {
        $this->createProgram(
            ['is_featured' => false, 'is_trending' => false],
            title: 'برنامج فئة ظاهرة'
        );

        MediaCategory::create(['key' => 'seerah', 'is_featured' => true, 'display_order' => 0]);

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee('برنامج فئة ظاهرة');
    }

    public function test_hub_shows_all_category_rows_when_categories_table_is_unseeded(): void
    {
        $this->createProgram(
            ['is_featured' => false, 'is_trending' => false],
            title: 'برنامج بدون تهيئة فئات'
        );

        $response = $this->get('/media');

        $response->assertStatus(200);
        $response->assertSee('برنامج بدون تهيئة فئات');
    }

    public function test_hub_shows_continue_watching_shell_and_lazy_endpoint_serves_the_row(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1, null, 'حلقة قيد المشاهدة');
        $user = User::factory()->create();

        EpisodeWatchProgress::create([
            'user_id' => $user->id,
            'episode_id' => $episode->id,
            'seconds' => 300,
            'completed' => false,
        ]);

        // The hub renders only the lazy-load shell (heading + shimmer) — the
        // per-user cards arrive via the JSON endpoint so the page markup stays
        // user-free and cache-safe.
        $response = $this->actingAs($user)->get('/media');

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.continue_watching'));
        $response->assertDontSee('حلقة قيد المشاهدة');

        $this->actingAs($user)
            ->getJson(route('public.media.continue-watching'))
            ->assertOk()
            ->assertJsonCount(1, 'items')
            ->assertJsonPath('items.0.id', $episode->id)
            ->assertJsonPath('items.0.title', 'حلقة قيد المشاهدة');
    }

    // ===================== PROGRAM DETAIL =====================

    public function test_program_page_returns_200_with_episodes(): void
    {
        $program = $this->createProgram(title: 'برنامج صفحة التفاصيل');
        $this->createEpisode($program, 1, null, 'حلقة التفاصيل الأولى');

        $response = $this->get('/media/'.$program->slug);

        $response->assertStatus(200);
        $response->assertSee('برنامج صفحة التفاصيل');
        $response->assertSee('حلقة التفاصيل الأولى');
    }

    public function test_program_page_returns_404_for_unknown_slug(): void
    {
        $this->get('/media/this-program-does-not-exist')->assertStatus(404);
    }

    // ===================== EPISODE PLAYER =====================

    public function test_episode_page_returns_200_with_player_for_video_episode(): void
    {
        $program = $this->createProgram();
        $this->createEpisode($program, 1, 'https://www.youtube.com/embed/cu68afTJVlk', 'حلقة المشغل');

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertStatus(200);
        $response->assertSee('حلقة المشغل');
        // Custom player: the SDK mounts by video id — no raw embed URL in the page.
        $response->assertSee('cu68afTJVlk');
        $response->assertSee('episode-player');
        $response->assertDontSee('youtube.com/embed/cu68afTJVlk');
    }

    public function test_episode_page_shows_unavailable_state_when_video_url_is_null(): void
    {
        $program = $this->createProgram();
        $this->createEpisode($program, 1, null, 'حلقة بدون فيديو');

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.video_unavailable_title'));
        $response->assertDontSee('youtube.com/embed/', false);
    }

    public function test_episode_page_shows_transcript_section_when_transcript_exists(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1, null, 'حلقة بنص كامل');

        MediaEpisodeTranslation::query()
            ->where('media_episode_id', $episode->id)
            ->where('locale', 'ar')
            ->update(['transcript' => "00:12 بسم الله الرحمن الرحيم\nسطر بدون طابع زمني"]);

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertStatus(200);
        $response->assertSee(trans('media.public.transcript'));
        $response->assertSee('بسم الله الرحمن الرحيم');
        $response->assertSee('00:12');
        $response->assertSee('سطر بدون طابع زمني');
    }

    public function test_episode_page_hides_transcript_section_when_transcript_is_empty(): void
    {
        $program = $this->createProgram();
        $this->createEpisode($program, 1, null, 'حلقة بلا نص');

        $response = $this->get('/media/'.$program->slug.'/episodes/1');

        $response->assertStatus(200);
        $response->assertDontSee(trans('media.public.transcript'));
    }

    public function test_episode_page_returns_404_for_unknown_episode_number(): void
    {
        $program = $this->createProgram();
        $this->createEpisode($program, 1, null, 'الحلقة الوحيدة');

        $this->get('/media/'.$program->slug.'/episodes/99')->assertStatus(404);
    }

    public function test_episode_page_returns_404_for_unknown_program(): void
    {
        $this->get('/media/no-such-program/episodes/1')->assertStatus(404);
    }

    public function test_episode_view_increments_views_count(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1, null, 'حلقة العدادات');

        $this->get('/media/'.$program->slug.'/episodes/1')->assertStatus(200);

        $this->assertSame(1, $episode->fresh()->views_count);
    }

    // ===================== UNIFIED SEARCH =====================

    public function test_unified_search_returns_media_programs(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
        $this->createProgram(title: 'سلسلة فريدة للبحث الموحد');

        $response = $this->postJson('/api/v1/search', [
            'query' => 'فريدة للبحث',
            'modules' => ['media'],
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.results.media.total'));
    }

    public function test_unified_search_across_all_modules_includes_media_without_error(): void
    {
        Sanctum::actingAs(User::factory()->create(), ['api:access']);
        $this->createProgram(title: 'برنامج البحث الشامل المميز');

        $response = $this->postJson('/api/v1/search', [
            'query' => 'البحث الشامل المميز',
        ]);

        $response->assertStatus(200);
        $this->assertSame(1, $response->json('data.results.media.total'));
    }
}
