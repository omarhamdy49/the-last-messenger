<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Models\Playlist;
use App\Domain\Shared\Support\VisitorFingerprint;
use App\Jobs\RecordPlaylistPlayJob;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Entry point for counting a playlist play (guests included). Same shape as
 * MediaViewRecorder: dedupe BEFORE dispatch — Cache::add is atomic, so a
 * repeat play in the window costs one cache call and never a queued job —
 * and the counter update runs in RecordPlaylistPlayJob on the `tracking` queue.
 */
final class PlaylistPlayRecorder
{
    /** One counted play per visitor per playlist per hour. */
    private const DEDUPE_TTL_SECONDS = 3600;

    public function record(Playlist $playlist, Request $request): void
    {
        // Best-effort by design: counting must never fail the listener's request.
        try {
            $hash = VisitorFingerprint::for($request, $request->user()?->id);

            if (! Cache::add(QuranCacheKeys::playlistPlayDedupe($playlist->id, $hash), 1, self::DEDUPE_TTL_SECONDS)) {
                return;
            }

            RecordPlaylistPlayJob::dispatch($playlist->id);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
