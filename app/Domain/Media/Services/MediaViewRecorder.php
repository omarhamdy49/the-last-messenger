<?php

declare(strict_types=1);

namespace App\Domain\Media\Services;

use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Support\MediaCacheKeys;
use App\Domain\Shared\Support\VisitorFingerprint;
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
            $hash = VisitorFingerprint::for(request(), $userId);

            if (! Cache::add(MediaCacheKeys::viewDedupe($type->value, $id, $hash), 1, self::DEDUPE_TTL_SECONDS)) {
                return;
            }

            RecordMediaViewJob::dispatch($type, $id, $userId, $hash);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
