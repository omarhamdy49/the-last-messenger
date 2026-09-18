<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Domain\Quran\Import\Reciters\ReciterFetchService;
use App\Domain\Quran\Repositories\ResourceRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Throwable;

/**
 * Admin "Fetch data / audio / timestamps" for one reciter, recorded as a SyncRun
 * (mode `fetch`) the admin page polls. One fetch per resource at a time.
 */
final class FetchReciterFromSourceJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable;

    public int $timeout = 3600;

    public int $tries = 1;

    public function __construct(
        public int $resourceId,
        public int $runId,
        public bool $data,
        public bool $audio,
        public bool $timestamps,
        public bool $download,
    ) {
        // `sync` is on every worker's queue list (see memory: supervisor worker registration).
        $this->onQueue('sync');
    }

    public function uniqueId(): string
    {
        return 'reciter-fetch:'.$this->resourceId;
    }

    public function handle(ReciterFetchService $service, ResourceRepositoryInterface $resources, SyncRepositoryInterface $sync): void
    {
        $run = $sync->findRun($this->runId);
        $resource = $resources->find($this->resourceId);

        if ($resource === null) {
            $run?->finish([], [['key' => 'resource:'.$this->resourceId, 'reason' => 'resource not found']]);

            return;
        }

        try {
            $report = $service->fetch($resource, $this->data, $this->audio, $this->timestamps, $this->download);
        } catch (Throwable $e) {
            $run?->finish([], [['key' => 'resource:'.$this->resourceId, 'reason' => $e->getMessage()]]);

            throw $e;
        }

        $run?->finish(
            ['rows_upserted' => $report->written + $report->updated, 'rows_deleted' => $report->deleted],
            $report->failed,
        );
    }
}
