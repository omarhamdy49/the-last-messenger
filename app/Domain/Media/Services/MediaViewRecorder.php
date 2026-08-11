<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Jobs\RecordMediaViewJob;
use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * Entry point for counting a public media view (guests included).
 *
 * Dedupe happens BEFORE dispatch — Cache::add is atomic (insert-or-fail), so
 * a repeat view within the hour costs one cache query and never a queued job.
 * The insert + counter update run in RecordMediaViewJob, off the request path.
 */
class MediaViewRecorder
{
    /** One counted view per visitor per item per hour. */
    private const DEDUPE_TTL_SECONDS = 3600;

    public function record(MediaViewableType $type, int $id): void
    {
        // Best-effort by design: view counting must never break a page render.
        try {
            $userId = auth()->id();
            $hash = $this->visitorHash($userId);

            if (! Cache::add(MediaCacheKeys::viewDedupe($type->value, $id, $hash), 1, self::DEDUPE_TTL_SECONDS)) {
                return;
            }

            RecordMediaViewJob::dispatch($type, $id, $userId, $hash);
        } catch (Throwable $e) {
            report($e);
        }
    }

    /**
     * Stable per-visitor fingerprint: user id when authenticated, session id
     * for web guests, IP + user agent for sessionless callers (token API).
     */
    private function visitorHash(?int $userId): string
    {
        if ($userId !== null) {
            return sha1('u'.$userId);
        }

        $request = request();
        $sessionId = $request->hasSession() ? $request->session()->getId() : null;

        return sha1($sessionId ?: $request->ip().'|'.(string) $request->userAgent());
    }
}
