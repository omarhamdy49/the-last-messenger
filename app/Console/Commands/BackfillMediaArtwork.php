<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Media\Models\MediaEpisode;
use App\Domain\Media\Models\MediaProgram;
use App\Domain\Media\Services\YouTubeSyncService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * One-off repair for content synced before HQ artwork existed: downloads the
 * best-resolution YouTube thumbnail (maxres → sd → hq) once per item and
 * saves it — program banner + poster (Spatie generates the small card
 * conversions), episode thumbnail (cards read the 'thumb' conversion, the
 * player poster reads the original). Safe to re-run — items with existing
 * media are skipped, so admin-uploaded artwork is never touched.
 */
class BackfillMediaArtwork extends Command
{
    protected $signature = 'media:backfill-artwork';

    protected $description = 'Attach HQ YouTube artwork to programs and episodes that have none';

    public function handle(YouTubeSyncService $sync): int
    {
        $programs = 0;
        $episodes = 0;
        $skipped = 0;

        MediaProgram::query()->orderBy('id')->chunkById(50, function ($chunk) use ($sync, &$programs, &$skipped): void {
            foreach ($chunk as $program) {
                try {
                    if ($sync->attachMissingArtwork($program)) {
                        $programs++;
                        $this->line("  ✓ program {$program->slug}");
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    report($e);
                    $skipped++;
                    $this->warn("  ✗ program {$program->slug}: {$e->getMessage()}");
                }
            }
        });

        MediaEpisode::query()->orderBy('id')->chunkById(100, function ($chunk) use ($sync, &$episodes, &$skipped): void {
            foreach ($chunk as $episode) {
                try {
                    if ($sync->attachMissingEpisodeThumbnail($episode)) {
                        $episodes++;
                    } else {
                        $skipped++;
                    }
                } catch (\Throwable $e) {
                    report($e);
                    $skipped++;
                    $this->warn("  ✗ episode {$episode->slug}: {$e->getMessage()}");
                }
            }
        });

        Log::info('media_artwork.backfilled', ['programs' => $programs, 'episodes' => $episodes, 'skipped' => $skipped]);
        $this->info("Attached artwork — programs: {$programs}, episodes: {$episodes}; skipped (existing media or no YouTube video): {$skipped}.");

        return self::SUCCESS;
    }
}
