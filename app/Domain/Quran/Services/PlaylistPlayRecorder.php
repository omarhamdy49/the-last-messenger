<?php

declare(strict_types=1);

namespace App\Domain\Quran\Services;

use App\Domain\Quran\Events\PlaylistPlayed;
use App\Domain\Quran\Models\Playlist;
use App\Domain\Shared\Support\VisitorFingerprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Throwable;

/**
 * Entry point for counting a playlist play (guests included). Same shape as
 * MediaViewRecorder: dedupe BEFORE dispatch — Cache::add is atomic, so a
 * repeat play in the window costs one cache call and never a queued job —
 * and the application queues the counter update when PlaylistPlayed is emitted.
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

            Event::dispatch(new PlaylistPlayed($playlist->id));
        } catch (Throwable $e) {
            report($e);
        }
    }
}
