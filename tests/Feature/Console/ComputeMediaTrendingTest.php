<?php

declare(strict_types=1);

namespace Tests\Feature\Console;

use App\Domain\Media\Services\MediaProgramService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Covers media:compute-trending — the 7-day roll-up of view events into
 * media_programs.trending_score — and the Trending read path (score order,
 * manual-flag fallback when no scores exist).
 */
final class ComputeMediaTrendingTest extends TestCase
{
    use RefreshDatabase;

    private function makeProgram(string $slug, bool $trendingFlag = false, int $score = 0): int
    {
        $programId = (int) DB::table('media_programs')->insertGetId([
            'slug' => $slug,
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2024,
            'rating' => 8.5,
            'is_featured' => false,
            'is_trending' => $trendingFlag,
            'trending_score' => $score,
            'is_published' => true,
            'views_count' => 0,
            'display_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('media_program_translations')->insert([
            'media_program_id' => $programId,
            'locale' => 'ar',
            'title' => "برنامج {$slug}",
            'description' => null,
            'host' => null,
            'is_ready' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $programId;
    }

    private function makeEpisode(int $programId, int $number): int
    {
        return (int) DB::table('media_episodes')->insertGetId([
            'program_id' => $programId,
            'season_number' => 1,
            'episode_number' => $number,
            'slug' => "trend-episode-{$programId}-{$number}",
            'video_url' => null,
            'duration_seconds' => 600,
            'views_count' => 0,
            'is_featured' => false,
            'is_published' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function insertViews(string $type, int $id, int $count, int $daysAgo = 1): void
    {
        $rows = [];
        for ($i = 0; $i < $count; $i++) {
            $rows[] = [
                'viewable_type' => $type,
                'viewable_id' => $id,
                'user_id' => null,
                'visitor_hash' => sha1("{$type}-{$id}-{$i}"),
                'created_at' => now()->subDays($daysAgo),
            ];
        }
        DB::table('media_view_events')->insert($rows);
    }

    public function test_command_rolls_up_program_and_episode_views_within_seven_days(): void
    {
        $quiet = $this->makeProgram('quiet-program');
        $busy = $this->makeProgram('busy-program');
        $episodeId = $this->makeEpisode($busy, 1);

        $this->insertViews('program', $busy, 2);          // direct program views
        $this->insertViews('episode', $episodeId, 3);     // rolled up to program
        $this->insertViews('program', $quiet, 5, daysAgo: 10); // outside the window

        $this->artisan('media:compute-trending')->assertSuccessful();

        $this->assertDatabaseHas('media_programs', ['id' => $busy, 'trending_score' => 5]);
        $this->assertDatabaseHas('media_programs', ['id' => $quiet, 'trending_score' => 0]);
    }

    public function test_command_resets_programs_that_fell_out_of_the_window(): void
    {
        $stale = $this->makeProgram('stale-program', score: 42);

        $this->artisan('media:compute-trending')->assertSuccessful();

        $this->assertDatabaseHas('media_programs', ['id' => $stale, 'trending_score' => 0]);
    }

    public function test_trending_read_path_orders_by_score_with_flag_fallback(): void
    {
        $service = app(MediaProgramService::class);

        // No scores anywhere → the manual flag drives the row.
        $flagged = $this->makeProgram('flagged-program', trendingFlag: true);
        $this->assertSame(
            [$flagged],
            $service->getTrending(12)->pluck('id')->all(),
            'With no computed scores the manual is_trending flag must drive the row.',
        );

        // Real scores exist → score order wins, flag ignored.
        $second = $this->makeProgram('second-program');
        $first = $this->makeProgram('first-program');
        $this->insertViews('program', $second, 1);
        $this->insertViews('program', $first, 3);

        $this->artisan('media:compute-trending')->assertSuccessful();

        $this->assertSame(
            [$first, $second],
            $service->getTrending(12)->pluck('id')->all(),
            'Programs with 7-day views must rank by score, most-watched first.',
        );
    }
}
