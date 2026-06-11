<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Domain\Media\Services\MediaProgramService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Publishes media programs whose scheduled publish_at has passed.
 * Scheduled hourly in routes/console.php.
 */
class PublishScheduledMediaPrograms extends Command
{
    protected $signature = 'media:publish-scheduled';

    protected $description = 'Publish media programs whose publish_at time has been reached';

    public function handle(MediaProgramService $programService): int
    {
        try {
            $published = $programService->publishDuePrograms();

            if ($published > 0) {
                Log::info("Published {$published} scheduled media program(s)");
            }

            $this->info("Published {$published} scheduled media program(s).");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            Log::error('Failed to publish scheduled media programs: '.$e->getMessage());
            $this->error('Failed to publish scheduled media programs: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
