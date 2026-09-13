<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Quran\Models\Playlist;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Adds one deduplicated play to a playlist's plays_count, off the request path.
 *
 * Single attempt on purpose: PlaylistPlayRecorder consumed the dedupe slot
 * before dispatching, so a retry would double-count. Losing one play on a
 * rare failure is the correct trade (same as RecordMediaViewJob).
 */
class RecordPlaylistPlayJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(public int $playlistId)
    {
        // Own queue: a play burst must never delay sync/default jobs.
        $this->onQueue('tracking');
    }

    public function handle(): void
    {
        // Query-builder UPDATE: no model events, so listing caches are not
        // invalidated per play — plays show up when those caches next refresh.
        // A playlist deleted in the meantime simply matches no row.
        Playlist::query()->whereKey($this->playlistId)->increment('plays_count');
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('playlist_play_job.failed', [
            'playlist_id' => $this->playlistId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['playlist-plays', "playlist:{$this->playlistId}"];
    }
}
