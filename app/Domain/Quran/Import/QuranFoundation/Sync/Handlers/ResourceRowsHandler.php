<?php

declare(strict_types=1);

namespace App\Domain\Quran\Import\QuranFoundation\Sync\Handlers;

use App\Domain\Quran\DTOs\ImportReport;
use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Import\QuranFoundation\Sync\SyncGroupHandler;
use App\Domain\Quran\Repositories\ImportRepositoryInterface;
use App\Domain\Quran\Repositories\SyncRepositoryInterface;
use Illuminate\Support\Facades\DB;

/**
 * Shared plumbing for groups whose rows belong to one quran_resources row
 * (translations, tafsirs, word glosses, audio files). Subclasses map QF records to
 * table rows.
 */
abstract class ResourceRowsHandler implements SyncGroupHandler
{
    /** @var array<int, true>|null */
    private ?array $verseIds = null;

    /** @var array<int, true>|null */
    private ?array $wordIds = null;

    public function __construct(
        protected readonly ImportRepositoryInterface $rows,
        protected readonly SyncRepositoryInterface $sync,
    ) {}

    abstract protected function kind(): ResourceKind;

    abstract protected function table(): string;

    /** @return list<string> */
    abstract protected function uniqueBy(): array;

    /** @return list<string> */
    abstract protected function updateColumns(): array;

    protected function lockColumn(): ?string
    {
        return null;
    }

    /**
     * Map one QF record to a row, or null (after reporting) when it cannot be stored.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>|null
     */
    abstract protected function map(int $resourceId, array $record, ImportReport $report): ?array;

    /**
     * Record types this group stores; others are reported, never silently dropped.
     *
     * @return list<string>
     */
    protected function recordTypes(): array
    {
        return [];
    }

    public function replace(int $qfResourceId, array $records, ImportReport $report): void
    {
        $resourceId = $this->localResource($qfResourceId, $report);
        if ($resourceId === null) {
            return;
        }

        $mapped = $this->mapAll($resourceId, $records, $report);
        $report->expected += count($records);
        $report->add($this->rows->replaceForOwner($this->table(), 'resource_id', $resourceId, $mapped, $this->uniqueBy(), $this->updateColumns(), $this->lockColumn()));
        $this->afterWrite($resourceId, $records);
        $this->sync->touchResource($resourceId, $this->table());
    }

    public function upsertRows(int $qfResourceId, string $recordType, array $records, ImportReport $report): void
    {
        if (! $this->acceptsRecordType($recordType, $report, $qfResourceId)) {
            return;
        }

        $resourceId = $this->localResource($qfResourceId, $report);
        if ($resourceId === null) {
            return;
        }

        $mapped = $this->mapAll($resourceId, $records, $report);
        $report->add($this->rows->upsert($this->table(), $mapped, $this->uniqueBy(), $this->updateColumns(), $this->lockColumn()));
        $this->sync->touchResource($resourceId, $this->table());
    }

    public function deleteRow(int $qfResourceId, string $recordType, string $recordKey, ImportReport $report): void
    {
        if (! $this->acceptsRecordType($recordType, $report, $qfResourceId)) {
            return;
        }

        $resourceId = $this->localResource($qfResourceId, $report);
        if ($resourceId === null) {
            return;
        }

        $query = DB::table($this->table())->where('resource_id', $resourceId)->where('qf_id', (int) $recordKey);
        if ($this->lockColumn() !== null && (clone $query)->where($this->lockColumn(), true)->exists()) {
            $report->skippedLocked++;

            return;
        }

        $report->deleted += $query->delete();
        $this->sync->touchResource($resourceId, $this->table());
    }

    public function deactivate(int $qfResourceId, ?string $reason, ImportReport $report): void
    {
        $resourceId = $this->localResource($qfResourceId, $report);
        if ($resourceId !== null) {
            $this->sync->deactivateResource($resourceId, $reason);
            $report->note("{$this->group()->value}:{$qfResourceId} deactivated".($reason !== null ? " ({$reason})" : ''));
        }
    }

    public function touch(int $qfResourceId): void
    {
        $resourceId = $this->sync->resourceId($this->kind(), $qfResourceId);
        if ($resourceId !== null) {
            DB::table('quran_resources')->where('id', $resourceId)->update(['last_synced_at' => now()]);
        }
    }

    /**
     * Hook for handlers that update the resource from the records (e.g. language).
     *
     * @param  list<array<string, mixed>>  $records
     */
    protected function afterWrite(int $resourceId, array $records): void {}

    protected function verseExists(int $verseId): bool
    {
        $this->verseIds ??= array_fill_keys(DB::table('quran_verses')->pluck('id')->map(static fn (mixed $v): int => (int) $v)->all(), true);

        return isset($this->verseIds[$verseId]);
    }

    protected function wordExists(int $wordId): bool
    {
        $this->wordIds ??= array_fill_keys(DB::table('quran_words')->pluck('id')->map(static fn (mixed $v): int => (int) $v)->all(), true);

        return isset($this->wordIds[$wordId]);
    }

    /**
     * @param  list<array<string, mixed>>  $records
     * @return list<array<string, mixed>>
     */
    private function mapAll(int $resourceId, array $records, ImportReport $report): array
    {
        $mapped = [];
        foreach ($records as $record) {
            $type = $record['record_type'] ?? null;
            if (is_string($type) && ! $this->acceptsRecordType($type, $report, $resourceId)) {
                continue;
            }
            $row = $this->map($resourceId, $record, $report);
            if ($row !== null) {
                $mapped[] = $row;
            }
        }

        return $mapped;
    }

    private function acceptsRecordType(string $recordType, ImportReport $report, int $id): bool
    {
        if ($this->recordTypes() === [] || in_array($recordType, $this->recordTypes(), true)) {
            return true;
        }

        $report->fail("{$this->group()->value}:{$id}:{$recordType}", 'record type not handled');

        return false;
    }

    private function localResource(int $qfResourceId, ImportReport $report): ?int
    {
        $id = $this->sync->resourceId($this->kind(), $qfResourceId);
        if ($id === null) {
            $report->fail("{$this->group()->value}:{$qfResourceId}", 'resource not in the local catalogue — run quran:qf:import-catalogue');
        }

        return $id;
    }
}
