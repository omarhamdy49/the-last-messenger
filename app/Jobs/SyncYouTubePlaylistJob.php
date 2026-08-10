<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Media\Models\YouTubeSyncRun;
use App\Domain\Media\Services\YouTubeSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Imports a YouTube playlist into a program in the background, advancing the
 * YouTubeSyncRun ledger the admin modal polls.
 *
 * Two attempts: the import is idempotent (episodes matched by video id), so
 * when the worker dies mid-run the retry resumes instead of leaving the run
 * stuck in "running" until MaxAttemptsExceeded finally marks it failed.
 */
class SyncYouTubePlaylistJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Long enough for a large playlist of per-video fetches. */
    public int $timeout = 3600;

    public int $tries = 2;

    public bool $deleteWhenMissingModels = true;

    public function __construct(public int $runId)
    {
        $this->onQueue('sync');
    }

    public function handle(YouTubeSyncService $service): void
    {
        $run = YouTubeSyncRun::find($this->runId);

        if ($run === null) {
            Log::warning('youtube_playlist_job.run_missing', ['run' => $this->runId]);

            return;
        }

        $service->processRun($run);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('youtube_playlist_job.failed', [
            'run' => $this->runId,
            'error' => $exception->getMessage(),
        ]);

        YouTubeSyncRun::find($this->runId)?->markFailed($exception->getMessage());
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['sync', 'youtube', "run:{$this->runId}"];
    }
}
