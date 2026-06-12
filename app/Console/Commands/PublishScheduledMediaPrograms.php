<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Media\Services\MediaEpisodeService;
use App\Domain\Media\Services\MediaProgramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Publishes media programs and episodes whose scheduled publish_at has passed.
 * Scheduled hourly in routes/console.php.
 */
class PublishScheduledMediaPrograms extends Command
{
    protected $signature = 'media:publish-scheduled';

    protected $description = 'Publish media programs and episodes whose publish_at time has been reached';

    public function handle(MediaProgramService $programService, MediaEpisodeService $episodeService): int
    {
        try {
            $programs = $programService->publishDuePrograms();
            $episodes = $episodeService->publishDueEpisodes();

            if ($programs > 0 || $episodes > 0) {
                Log::info("Published {$programs} scheduled media program(s) and {$episodes} episode(s)");
            }

            $this->info("Published {$programs} scheduled media program(s) and {$episodes} episode(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Failed to publish scheduled media: '.$e->getMessage());
            $this->error('Failed to publish scheduled media: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
