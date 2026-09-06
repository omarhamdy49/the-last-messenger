<?php

declare(strict_types=1);

namespace Tests\Feature\Domain\Media;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaEpisodeTranslation;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Models\MediaProgramTranslation;
use App\Domain\Media\Repositories\MediaEpisodeRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Integration test for episode reordering with realistic data (50+ episodes).
 *
 * Verifies that MediaEpisodeRepository::reorderEpisodes() correctly handles
 * large datasets while maintaining low query counts through optimized SQL patterns.
 */
final class EpisodeReorderingTest extends TestCase
{
    use RefreshDatabase;

    private MediaEpisodeRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->repository = new MediaEpisodeRepository();
    }

    private function createProgram(string $title = 'برنامج السيرة التجريبي'): MediaProgram
    {
        $program = MediaProgram::create([
            'slug' => 'program-'.uniqid(),
            'category' => 'seerah',
            'maturity_rating' => 'all',
            'year' => 2023,
            'rating' => 7.5,
            'is_featured' => false,
            'is_trending' => false,
            'views_count' => 0,
            'display_order' => 0,
        ]);

        MediaProgramTranslation::create([
            'media_program_id' => $program->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $program;
    }

    private function createEpisode(
        MediaProgram $program,
        int $seasonNumber,
        int $episodeNumber,
        string $title = 'حلقة تجريبية'
    ): MediaEpisode {
        $episode = MediaEpisode::create([
            'program_id' => $program->id,
            'season_number' => $seasonNumber,
            'episode_number' => $episodeNumber,
            'slug' => "episode-{$seasonNumber}-{$episodeNumber}-".uniqid(),
            'video_url' => 'https://www.youtube.com/embed/test'.uniqid(),
            'duration_seconds' => 600,
            'views_count' => 0,
            'is_featured' => false,
        ]);

        MediaEpisodeTranslation::create([
            'media_episode_id' => $episode->id,
            'locale' => 'ar',
            'title' => $title,
            'is_ready' => true,
        ]);

        return $episode;
    }

    // ------------------------------------------------------------------
    // Realistic Data Tests (50+ episodes)
    // ------------------------------------------------------------------

    public function test_reorders_50_plus_episodes_efficiently(): void
    {
        $program = $this->createProgram();

        // Create 60 episodes in season 1
        $episodes = [];
        for ($i = 1; $i <= 60; $i++) {
            $episodes[] = $this->createEpisode($program, 1, $i, "الحلقة {$i}");
        }

        // Reverse the order (episode 60 becomes 1, episode 1 becomes 60)
        $reversedIds = array_reverse(array_map(fn ($ep) => $ep->id, $episodes));

        // Enable query logging to verify efficiency
        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, $reversedIds);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify the reordering occurred correctly
        $reloadedEpisodes = MediaEpisode::where('program_id', $program->id)
            ->orderBy('episode_number')
            ->get();

        // Episode that was number 60 should now be number 1
        $this->assertSame($episodes[59]->id, $reloadedEpisodes->first()->id);
        $this->assertSame(1, $reloadedEpisodes->first()->episode_number);

        // Episode that was number 1 should now be number 60
        $this->assertSame($episodes[0]->id, $reloadedEpisodes->last()->id);
        $this->assertSame(60, $reloadedEpisodes->last()->episode_number);

        // Analyze query types
        $updateQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'update') &&
                   str_contains(strtolower($query['query']), 'media_episodes');
        });

        $selectQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'select');
        });

        // Should use CASE-based UPDATE (max 2 UPDATE queries: increment + case-based)
        $this->assertLessThanOrEqual(2, count($updateQueries), 'Should use optimized CASE-based UPDATE pattern');

        // Should not have N+1 SELECT queries
        $this->assertLessThan(10, count($selectQueries), 'Should not have excessive SELECT queries');

        // Total queries should be much less than 120 (would be 60+ with N+1 pattern)
        $this->assertLessThan(30, count($queries), 'Total queries should be well under 30 for 60 episodes');
    }

    public function test_handles_partial_reordering_efficiently(): void
    {
        $program = $this->createProgram();

        // Create 80 episodes
        $episodes = [];
        for ($i = 1; $i <= 80; $i++) {
            $episodes[] = $this->createEpisode($program, 1, $i, "الحلقة {$i}");
        }

        // Reorder only first 20 episodes (swap 1-10 with 11-20)
        $reorderIds = array_merge(
            array_slice(array_map(fn ($ep) => $ep->id, $episodes), 10, 10),  // Episodes 11-20
            array_slice(array_map(fn ($ep) => $ep->id, $episodes), 0, 10)    // Episodes 1-10
        );

        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, $reorderIds);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify partial reordering
        $reloadedEpisodes = MediaEpisode::where('program_id', $program->id)
            ->whereBetween('episode_number', [1, 20])
            ->orderBy('episode_number')
            ->get();

        // Episode that was number 11 should now be number 1
        $this->assertSame($episodes[10]->id, $reloadedEpisodes->first()->id);

        // Episode that was number 1 should now be number 11
        $this->assertSame($episodes[0]->id, $reloadedEpisodes->where('episode_number', 11)->first()->id);

        // Episodes 21-80 should remain unchanged
        $unchangedEpisode = MediaEpisode::where('program_id', $program->id)
            ->where('episode_number', 21)
            ->first();
        $this->assertSame($episodes[20]->id, $unchangedEpisode->id);

        // Verify query efficiency for partial reorder
        $updateQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'update');
        });

        $this->assertLessThanOrEqual(2, count($updateQueries), 'Partial reorder should use CASE-based UPDATE');
        $this->assertLessThan(25, count($queries), 'Partial reorder should be efficient');
    }

    public function test_correctly_handles_multiple_seasons(): void
    {
        $program = $this->createProgram();

        // Create 30 episodes in season 1
        $season1Episodes = [];
        for ($i = 1; $i <= 30; $i++) {
            $season1Episodes[] = $this->createEpisode($program, 1, $i, "موسم 1 - الحلقة {$i}");
        }

        // Create 25 episodes in season 2
        $season2Episodes = [];
        for ($i = 1; $i <= 25; $i++) {
            $season2Episodes[] = $this->createEpisode($program, 2, $i, "موسم 2 - الحلقة {$i}");
        }

        // Reverse only season 1 episodes
        $reversedSeason1Ids = array_reverse(array_map(fn ($ep) => $ep->id, $season1Episodes));

        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, $reversedSeason1Ids);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify season 1 was reordered
        $reloadedSeason1 = MediaEpisode::where('program_id', $program->id)
            ->where('season_number', 1)
            ->orderBy('episode_number')
            ->get();

        $this->assertSame($season1Episodes[29]->id, $reloadedSeason1->first()->id);
        $this->assertSame(1, $reloadedSeason1->first()->episode_number);

        // Verify season 2 remained unchanged
        $reloadedSeason2 = MediaEpisode::where('program_id', $program->id)
            ->where('season_number', 2)
            ->orderBy('episode_number')
            ->get();

        $this->assertSame($season2Episodes[0]->id, $reloadedSeason2->first()->id);
        $this->assertSame(1, $reloadedSeason2->first()->episode_number);

        // Verify efficient query pattern
        $this->assertLessThan(25, count($queries), 'Multi-season reorder should be efficient');
    }

    public function test_performance_with_maximum_realistic_dataset(): void
    {
        $program = $this->createProgram();

        // Create 100 episodes (realistic maximum for a long-running series)
        $episodes = [];
        for ($i = 1; $i <= 100; $i++) {
            $episodes[] = $this->createEpisode($program, 1, $i, "الحلقة {$i}");
        }

        // Shuffle the order randomly
        $shuffledIds = array_map(fn ($ep) => $ep->id, $episodes);
        shuffle($shuffledIds);

        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, $shuffledIds);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Verify all episodes are still present with correct numbers
        $reloadedEpisodes = MediaEpisode::where('program_id', $program->id)
            ->orderBy('episode_number')
            ->get();

        $this->assertCount(100, $reloadedEpisodes);

        // Verify sequential numbering
        foreach ($reloadedEpisodes as $index => $episode) {
            $this->assertSame($index + 1, $episode->episode_number);
        }

        // Analyze query efficiency for large dataset
        $updateQueries = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'update') &&
                   str_contains(strtolower($query['query']), 'media_episodes');
        });

        $caseBasedUpdates = array_filter($queries, function ($query) {
            return str_contains(strtolower($query['query']), 'case') &&
                   str_contains(strtolower($query['query']), 'update');
        });

        // Should use CASE-based UPDATE pattern (key optimization)
        $this->assertCount(1, $caseBasedUpdates, 'Should use exactly one CASE-based UPDATE for efficiency');

        // Should have exactly 2 UPDATE queries (increment + case-based)
        $this->assertCount(2, $updateQueries, 'Should have exactly 2 UPDATE queries (increment + CASE)');

        // Total queries should be much less than 200 (would be 100+ with N+1 pattern)
        $this->assertLessThan(40, count($queries), 'Total queries should be well under 40 for 100 episodes');
    }

    public function test_maintains_correctness_with_complex_reordering(): void
    {
        $program = $this->createProgram();

        // Create 50 episodes with specific ordering
        $episodes = [];
        for ($i = 1; $i <= 50; $i++) {
            $episodes[] = $this->createEpisode($program, 1, $i, "الحلقة {$i}");
        }

        // Create a complex reordering pattern: move every 5th episode to the front
        $reorderedIds = [];

        // First, add every 5th episode (5, 10, 15, 20, ...)
        for ($i = 4; $i < 50; $i += 5) {
            $reorderedIds[] = $episodes[$i]->id;
        }

        // Then add the remaining episodes in original order
        for ($i = 0; $i < 50; $i++) {
            if (($i + 1) % 5 !== 0) {
                $reorderedIds[] = $episodes[$i]->id;
            }
        }

        $this->repository->reorderEpisodes($program->id, $reorderedIds);

        // Verify correctness
        $reloadedEpisodes = MediaEpisode::where('program_id', $program->id)
            ->orderBy('episode_number')
            ->get();

        // Episode that was number 5 should now be number 1
        $this->assertSame($episodes[4]->id, $reloadedEpisodes->first()->id);
        $this->assertSame(1, $reloadedEpisodes->first()->episode_number);

        // Episode that was number 10 should now be number 2
        $this->assertSame($episodes[9]->id, $reloadedEpisodes[1]->id);
        $this->assertSame(2, $reloadedEpisodes[1]->episode_number);

        // All episodes should have sequential numbers 1-50
        foreach ($reloadedEpisodes as $index => $episode) {
            $this->assertSame($index + 1, $episode->episode_number);
        }
    }

    public function test_handles_single_episode_efficiently(): void
    {
        $program = $this->createProgram();
        $episode = $this->createEpisode($program, 1, 1);

        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, [$episode->id]);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Even for a single episode, should not have excessive queries
        $this->assertLessThan(20, count($queries), 'Single episode reorder should be efficient');

        // Episode should still be number 1
        $this->assertSame(1, $episode->fresh()->episode_number);
    }

    public function test_handles_empty_reorder_gracefully(): void
    {
        $program = $this->createProgram();

        // Create some episodes but reorder with empty array
        for ($i = 1; $i <= 10; $i++) {
            $this->createEpisode($program, 1, $i);
        }

        DB::enableQueryLog();
        $this->repository->reorderEpisodes($program->id, []);
        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        // Should handle gracefully with minimal queries
        $this->assertLessThan(10, count($queries), 'Empty reorder should be handled efficiently');

        // Episodes should remain unchanged
        $episodes = MediaEpisode::where('program_id', $program->id)
            ->orderBy('episode_number')
            ->get();

        $this->assertCount(10, $episodes);
        $this->assertSame(1, $episodes->first()->episode_number);
        $this->assertSame(10, $episodes->last()->episode_number);
    }

    public function test_query_count_is_independent_of_episode_count(): void
    {
        $program = $this->createProgram();

        // Test with different episode counts to verify O(1) query pattern
        $testSizes = [10, 50, 100];
        $queryCounts = [];

        foreach ($testSizes as $size) {
            // Clean up previous episodes
            MediaEpisode::where('program_id', $program->id)->delete();

            // Create episodes
            $episodes = [];
            for ($i = 1; $i <= $size; $i++) {
                $episodes[] = $this->createEpisode($program, 1, $i);
            }

            // Reverse the order
            $reversedIds = array_reverse(array_map(fn ($ep) => $ep->id, $episodes));

            DB::enableQueryLog();
            $this->repository->reorderEpisodes($program->id, $reversedIds);
            $queries = DB::getQueryLog();
            DB::disableQueryLog();

            // Count UPDATE queries (the critical metric)
            $updateQueries = array_filter($queries, function ($query) {
                return str_contains(strtolower($query['query']), 'update') &&
                       str_contains(strtolower($query['query']), 'media_episodes');
            });

            $queryCounts[$size] = count($updateQueries);
        }

        // Verify that UPDATE query count is constant (2: increment + CASE-based)
        $this->assertSame(2, $queryCounts[10], '10 episodes should use 2 UPDATE queries');
        $this->assertSame(2, $queryCounts[50], '50 episodes should use 2 UPDATE queries');
        $this->assertSame(2, $queryCounts[100], '100 episodes should use 2 UPDATE queries');

        // All should be equal (O(1) query pattern)
        $this->assertSame($queryCounts[10], $queryCounts[50]);
        $this->assertSame($queryCounts[50], $queryCounts[100]);
    }
}
