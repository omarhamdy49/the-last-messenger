<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\SyncMutationType;
use App\Domain\Quran\Enums\SyncResourceGroup;
use App\Domain\Quran\Import\QuranFoundation\Endpoints;
/**
 * Applies one QF Content Sync mutation through the handler for its group:
 *
 *   RESOURCE_CREATE / RESOURCE_INVALIDATE → fetch snapshot, replace local rows
 *   RESOURCE_DELETE                        → hide the resource
 *   RESOURCE_UPDATE                        → freshness marker only
 *   ROW_CREATE / ROW_UPDATE                → upsert `data` by record_type + record_key
 *   ROW_DELETE                             → delete by record_type + record_key
 *
 * Unknown types, groups and record types are itemised in the report, never dropped.
 */
use RuntimeException;
use Throwable;

final class MutationApplier
{
    public int $snapshotsFetched = 0;

    /** @var array<string, SyncGroupHandler> */
    private array $handlers = [];

    /**
     * @param  iterable<SyncGroupHandler>  $handlers
     */
    public function __construct(private readonly Endpoints $qf, iterable $handlers)
    {
        foreach ($handlers as $handler) {
            $this->handlers[$handler->group()->value] = $handler;
        }
    }

    public function handles(SyncResourceGroup $group): bool
    {
        return isset($this->handlers[$group->value]);
    }

    /**
     * @param  array<string, mixed>  $mutation
     */
    public function apply(array $mutation, ImportReport $report): void
    {
        $type = SyncMutationType::tryFrom((string) ($mutation['type'] ?? ''));
        $group = SyncResourceGroup::tryFrom((string) ($mutation['resource_group'] ?? ''));
        $resourceId = (int) ($mutation['resource_id'] ?? 0);
        $label = ($mutation['resource_group'] ?? '?').':'.$resourceId.' '.($mutation['type'] ?? '?');

        if ($type === null || $group === null || $resourceId <= 0) {
            $report->fail($label, 'unknown mutation type, group or resource id');

            return;
        }

        $handler = $this->handlers[$group->value] ?? null;
        if ($handler === null) {
            $report->fail($label, 'no handler for this resource group');

            return;
        }

        switch ($type) {
            case SyncMutationType::RESOURCE_CREATE:
            case SyncMutationType::RESOURCE_INVALIDATE:
                try {
                    $snapshot = is_string($mutation['snapshot_url'] ?? null)
                        ? $this->qf->followRelative($mutation['snapshot_url'])
                        : $this->qf->snapshot($group, $resourceId);
                } catch (Throwable $e) {
                    // The client labels every follow `resources/sync:follow`, so without
                    // this the run report says a snapshot failed but not which one, and a
                    // large tafsir that drops mid-download is indistinguishable from a
                    // feed error. Rethrown, not swallowed: the token must not advance past
                    // a change we did not apply.
                    throw new RuntimeException("snapshot for {$label} failed: ".$e->getMessage(), 0, $e);
                }

                $this->snapshotsFetched++;
                $records = $snapshot['records'] ?? null;
                if (! is_array($records)) {
                    $report->fail($label, 'snapshot has no records');

                    return;
                }
                $handler->replace($resourceId, array_values($records), $report);
                break;

            case SyncMutationType::RESOURCE_DELETE:
                $handler->deactivate($resourceId, isset($mutation['unavailable_reason']) ? (string) $mutation['unavailable_reason'] : null, $report);
                break;

            case SyncMutationType::RESOURCE_UPDATE:
                $handler->touch($resourceId);
                break;

            case SyncMutationType::ROW_CREATE:
            case SyncMutationType::ROW_UPDATE:
                $data = $mutation['data'] ?? null;
                $recordType = (string) ($mutation['record_type'] ?? '');
                if (! is_array($data) || $recordType === '') {
                    $report->fail($label, 'row mutation without data or record_type');

                    return;
                }
                $handler->upsertRows($resourceId, $recordType, [$data + ['record_type' => $recordType]], $report);
                break;

            case SyncMutationType::ROW_DELETE:
                $recordType = (string) ($mutation['record_type'] ?? '');
                $recordKey = (string) ($mutation['record_key'] ?? '');
                if ($recordType === '' || $recordKey === '') {
                    $report->fail($label, 'row delete without record_type or record_key');

                    return;
                }
                $handler->deleteRow($resourceId, $recordType, $recordKey, $report);
                break;
        }
    }
}
