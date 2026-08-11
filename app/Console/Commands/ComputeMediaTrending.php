<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Support\MediaCacheKeys;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Recomputes media_programs.trending_score from real view events: direct
 * program views plus episode views rolled up to their program, over the
 * trailing 7 days. Scheduled every 30 minutes in routes/console.php; the
 * Trending row cache is dropped here so it refreshes on this cadence rather
 * than the generic 30-minute TTL.
 */
class ComputeMediaTrending extends Command
{
    protected $signature = 'media:compute-trending';

    protected $description = 'Recompute media program trending scores from the last 7 days of view events';

    public function handle(): int
    {
        try {
            $since = now()->subDays(7);

            $programViews = DB::table('media_view_events')
                ->selectRaw('viewable_id AS program_id, COUNT(*) AS views')
                ->where('viewable_type', MediaViewableType::Program->value)
                ->where('created_at', '>=', $since)
                ->groupBy('viewable_id');

            $episodeViews = DB::table('media_view_events')
                ->join('media_episodes', 'media_episodes.id', '=', 'media_view_events.viewable_id')
                ->selectRaw('media_episodes.program_id AS program_id, COUNT(*) AS views')
                ->where('media_view_events.viewable_type', MediaViewableType::Episode->value)
                ->where('media_view_events.created_at', '>=', $since)
                ->groupBy('media_episodes.program_id');

            $scores = DB::query()
                ->fromSub($programViews->unionAll($episodeViews), 'views')
                ->selectRaw('program_id, SUM(views) AS score')
                ->groupBy('program_id')
                ->pluck('score', 'program_id');

            // Reset programs that fell out of the window, then write the rest.
            // ponytail: per-row updates — batch CASE update if programs > ~1k.
            MediaProgram::query()
                ->whereNotIn('id', $scores->keys())
                ->where('trending_score', '!=', 0)
                ->update(['trending_score' => 0]);

            foreach ($scores as $programId => $score) {
                MediaProgram::query()
                    ->whereKey($programId)
                    ->update(['trending_score' => (int) $score]);
            }

            // Refresh the public Trending row on this command's cadence.
            Cache::forget(MediaCacheKeys::programsTrendingByScore(12));

            Log::info('media_trending.computed', ['programs' => $scores->count()]);
            $this->info("Trending scores updated for {$scores->count()} program(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('media_trending.failed', ['error' => $e->getMessage()]);
            $this->error('Failed to compute trending scores: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
