<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Shared\Services\ArtisanCommandService;
use App\Models\CommandRun;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Runs one whitelisted Artisan command in the background, advancing the
 * CommandRun row the settings modal polls.
 *
 * Single attempt: not every command is idempotent, and silently re-running an
 * import after a worker died would be worse than reporting the failure.
 */
class RunArtisanCommandJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Long enough for a full Quran/Hadith import off an external API. */
    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(public int $runId)
    {
        // 'sync' is already in the dev/production worker's queue list and is
        // where the other long-running imports live.
        $this->onQueue('sync');
    }

    public function handle(ArtisanCommandService $service): void
    {
        $run = CommandRun::find($this->runId);

        if ($run === null) {
            Log::warning('artisan_command_job.run_missing', ['run' => $this->runId]);

            return;
        }

        $service->execute($run);
    }

    public function failed(Throwable $exception): void
    {
        Log::error('artisan_command_job.failed', [
            'run' => $this->runId,
            'error' => $exception->getMessage(),
        ]);

        CommandRun::find($this->runId)?->forceFill([
            'status' => CommandRun::STATUS_FAILED,
            'error' => mb_substr($exception->getMessage(), 0, 60000),
            'finished_at' => now(),
        ])->save();
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['artisan', "run:{$this->runId}"];
    }
}
