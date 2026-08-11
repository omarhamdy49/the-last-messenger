<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Media\Enums\MediaViewableType;
use App\Domain\Media\Models\MediaViewEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Log;

/**
 * Persists one deduplicated media view: an event row (feeds trending +
 * analytics) and the item's all-time views_count, both off the request path.
 *
 * Single attempt on purpose: MediaViewRecorder already consumed the dedupe
 * slot before dispatching, so a retry would double-insert the event and
 * double-increment the counter. Losing one view on a rare failure is the
 * correct trade.
 */
class RecordMediaViewJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $tries = 1;

    public int $timeout = 30;

    public function __construct(
        public MediaViewableType $type,
        public int $viewableId,
        public ?int $userId,
        public string $visitorHash,
    ) {
        // Own queue: a view burst must never delay sync/default jobs.
        $this->onQueue('tracking');
    }

    public function handle(): void
    {
        MediaViewEvent::query()->create([
            'viewable_type' => $this->type,
            'viewable_id' => $this->viewableId,
            'user_id' => $this->userId,
            'visitor_hash' => $this->visitorHash,
            'created_at' => now(),
        ]);

        // Plain UPDATE without model hydration; stale cached cards may show
        // the counter up to 30 min behind, which is cosmetic.
        $this->type->modelClass()::query()
            ->whereKey($this->viewableId)
            ->increment('views_count');
    }

    public function failed(\Throwable $exception): void
    {
        Log::warning('media_view_job.failed', [
            'type' => $this->type->value,
            'viewable_id' => $this->viewableId,
            'error' => $exception->getMessage(),
        ]);
    }

    /**
     * @return list<string>
     */
    public function tags(): array
    {
        return ['media-views', "{$this->type->value}:{$this->viewableId}"];
    }
}
