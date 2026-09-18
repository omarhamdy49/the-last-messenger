<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Enums\SyncRunMode;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use App\Domain\Quran\Services\QuranCacheKeys;
use Throwable;

/**
 * QF Content Sync over every enabled resource.
 *
 * - The resources filter is built canonically from the database, so the same set
 *   of enabled resources always produces the same string (QF binds a sync token to
 *   its filter; enabling a resource therefore starts a new bootstrap).
 * - Pages are followed until `has_more` is false; the sync token is stored only after
 *   the final page applied without an exception.
 * - Every run is recorded in quran_sync_runs with reconciled counts.
 */
final class ContentSyncService
{
    private const MAX_PAGES = 1000;

    public function __construct(
        private readonly Endpoints $qf,
        private readonly MutationApplier $applier,
        private readonly SyncRepositoryInterface $sync,
    ) {}

    public function canonicalFilter(): string
    {
        $groups = [];
        foreach ($this->sync->syncEnabledQfIds() as $kind => $ids) {
            $group = ResourceKind::from($kind)->syncGroup();
            if ($group !== null && $this->applier->handles($group)) {
                $groups[$group->value] = $ids;
            }
        }

        $mushafs = $this->sync->activeMushafIds();
        if ($mushafs !== []) {
            $groups[SyncResourceGroup::MUSHAFS->value] = $mushafs;
        }

        ksort($groups);
        $parts = [];
        foreach ($groups as $group => $ids) {
            $ids = array_values(array_unique(array_map('intval', $ids)));
            sort($ids);
            $parts[] = $group.':'.implode(',', $ids);
        }

        return implode(';', $parts);
    }

    /**
     * @return array{run: SyncRun, report: ImportReport}
     */
    public function run(bool $forceBootstrap = false, ?string $filterOverride = null, int $perPage = 100, ?int $userId = null): array
    {
        $filter = $filterOverride ?? $this->canonicalFilter();
        $report = new ImportReport('sync');

        if ($filter === '') {
            $report->note('nothing to sync: no resource has sync enabled and no mushaf is active');

            return ['run' => $this->sync->startRun(SyncRunMode::INCREMENTAL, null, $userId), 'report' => $this->finish($report)];
        }

        $state = $this->sync->stateFor($filter);
        $bootstrap = $forceBootstrap || $state->sync_token === null;
        $run = $this->sync->startRun($bootstrap ? SyncRunMode::BOOTSTRAP : SyncRunMode::INCREMENTAL, $filter, $userId);
        $this->sync->markRunning($state);
        $this->applier->snapshotsFetched = 0;

        $query = ['resources' => $filter, 'per_page' => max(1, min(100, $perPage))];
        $query += $bootstrap ? ['bootstrap' => 'true'] : ['sync_token' => $state->sync_token];

        $mutations = 0;
        try {
            $page = $this->qf->sync($query);
            $pages = 1;

            while (true) {
                $body = $page['sync'] ?? null;
                if (! is_array($body)) {
                    throw new \RuntimeException('sync response has no "sync" object');
                }

                foreach ($body['mutations'] ?? [] as $mutation) {
                    $this->applier->apply($mutation, $report);
                    $mutations++;
                }

                if (! ($body['has_more'] ?? false)) {
                    $token = $body['next_sync_token'] ?? null;
                    if (! is_string($token) || $token === '') {
                        throw new \RuntimeException('final sync page has no next_sync_token');
                    }
                    $this->sync->markSucceeded($state, $token, (int) ($body['sync_until_sequence'] ?? 0));
                    break;
                }

                $next = $body['next_page_url'] ?? null;
                if (! is_string($next) || $next === '') {
                    throw new \RuntimeException('sync page has has_more=true but no next_page_url');
                }
                if (++$pages > self::MAX_PAGES) {
                    throw new \RuntimeException('sync exceeded '.self::MAX_PAGES.' pages');
                }
                $page = $this->qf->followRelative($next);
            }
        } catch (Throwable $e) {
            // The token is not advanced, so the next run repeats from the last good point.
            $this->sync->markFailed($state, $e->getMessage());
            $report->fail('sync', $e->getMessage());
            report($e);
        }

        $this->finish($report);

        $run->finish([
            'mutations_applied' => $mutations,
            'snapshots_fetched' => $this->applier->snapshotsFetched,
            'rows_upserted' => $report->written + $report->updated,
            'rows_deleted' => $report->deleted,
            'rows_skipped_locked' => $report->skippedLocked,
        ], $report->failed);

        return ['run' => $run, 'report' => $report];
    }

    private function finish(ImportReport $report): ImportReport
    {
        QuranCacheKeys::flushResources();
        QuranCacheKeys::flushReciters();

        return $report;
    }
}
