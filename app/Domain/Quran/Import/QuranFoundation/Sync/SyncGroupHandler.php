<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\SyncResourceGroup;

/**
 * Applies QF Content Sync data for one resource group: a full snapshot replace, and
 * row-level upserts/deletes from incremental mutations. `$qfResourceId` is always
 * QF's `resource_id` for the group.
 */
interface SyncGroupHandler
{
    public function group(): SyncResourceGroup;

    /**
     * Replace every local row of the resource with the snapshot records.
     *
     * @param  list<array<string, mixed>>  $records
     */
    public function replace(int $qfResourceId, array $records, ImportReport $report): void;

    /**
     * Upsert rows of one record type (ROW_CREATE / ROW_UPDATE `data` payloads).
     *
     * @param  list<array<string, mixed>>  $records
     */
    public function upsertRows(int $qfResourceId, string $recordType, array $records, ImportReport $report): void;

    public function deleteRow(int $qfResourceId, string $recordType, string $recordKey, ImportReport $report): void;

    /**
     * RESOURCE_DELETE: hide the resource locally (rows are kept).
     */
    public function deactivate(int $qfResourceId, ?string $reason, ImportReport $report): void;

    /**
     * RESOURCE_UPDATE: freshness marker only.
     */
    public function touch(int $qfResourceId): void;
}
