<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncRunMode;
use App\Domain\Quran\Models\Mushaf;
use App\Domain\Quran\Models\QuranResource;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Models\SyncState;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class EloquentSyncRepository implements SyncRepositoryInterface
{
    public function stateFor(string $filter): SyncState
    {
        return SyncState::query()->firstOrCreate(
            ['filter_hash' => SyncState::hashFilter($filter)],
            ['resources_filter' => $filter, 'status' => SyncState::STATUS_IDLE],
        );
    }

    public function markRunning(SyncState $state): void
    {
        $state->forceFill(['status' => SyncState::STATUS_RUNNING, 'last_run_at' => now()])->save();
    }

    public function markSucceeded(SyncState $state, string $token, int $sequence): void
    {
        $state->forceFill([
            'status' => SyncState::STATUS_IDLE,
            'sync_token' => $token,
            'last_sequence' => max($sequence, (int) $state->last_sequence),
            'last_error' => null,
        ])->save();
    }

    public function markFailed(SyncState $state, string $error): void
    {
        $state->forceFill(['status' => SyncState::STATUS_FAILED, 'last_error' => mb_substr($error, 0, 2000)])->save();
    }

    public function startRun(SyncRunMode $mode, ?string $filter, ?int $userId): SyncRun
    {
        return SyncRun::query()->create([
            'mode' => $mode,
            'resources_filter' => $filter,
            'started_at' => now(),
            'status' => SyncRun::STATUS_RUNNING,
            'triggered_by' => $userId,
        ]);
    }

    public function syncEnabledQfIds(): array
    {
        $rows = DB::table('quran_resources')
            ->select(['kind', 'qf_id'])
            ->where('sync_enabled', true)
            ->whereNotNull('qf_id')
            ->whereIn('kind', array_map(
                static fn (ResourceKind $k): string => $k->value,
                array_values(array_filter(ResourceKind::cases(), static fn (ResourceKind $k): bool => $k->syncGroup() !== null)),
            ))
            ->orderBy('kind')->orderBy('qf_id')
            ->get();

        $byKind = [];
        foreach ($rows as $row) {
            $byKind[$row->kind][] = (int) $row->qf_id;
        }

        return $byKind;
    }

    public function activeMushafIds(): array
    {
        return Mushaf::query()->active()->orderBy('id')->pluck('id')->map(static fn (mixed $v): int => (int) $v)->all();
    }

    public function resourceId(ResourceKind $kind, int $qfId): ?int
    {
        $id = QuranResource::query()->ofKind($kind)->where('qf_id', $qfId)->value('id');

        return $id === null ? null : (int) $id;
    }

    public function touchResource(int $resourceId, string $table): void
    {
        DB::table('quran_resources')->where('id', $resourceId)->update([
            'records_count' => DB::table($table)->where('resource_id', $resourceId)->count(),
            'last_synced_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function deactivateResource(int $resourceId, ?string $reason): void
    {
        $resource = QuranResource::query()->select(['id', 'metadata'])->findOrFail($resourceId);
        $metadata = $resource->metadata ?? [];
        $metadata['unavailable_reason'] = $reason ?? 'removed by Quran Foundation';
        $metadata['unavailable_at'] = now()->toIso8601String();

        $resource->forceFill(['is_active' => false, 'is_default' => false, 'sync_enabled' => false, 'metadata' => $metadata])->save();
    }

    public function touchMushaf(int $mushafId): void
    {
        DB::table('quran_mushafs')->where('id', $mushafId)->update([
            'records_count' => DB::table('quran_mushaf_words')->where('mushaf_id', $mushafId)->count(),
            'last_synced_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * quran_mushafs has no metadata column; the reason is recorded in the sync run.
     */
    public function deactivateMushaf(int $mushafId, ?string $reason): void
    {
        DB::table('quran_mushafs')->where('id', $mushafId)->update(['is_active' => false, 'is_default' => false, 'updated_at' => now()]);
    }

    public function lastSucceededAt(): ?string
    {
        $latest = SyncRun::query()
            ->where('status', 'succeeded')
            ->max('finished_at');

        return $latest === null ? null : (string) $latest;
    }

    public function recentRuns(int $limit): Collection
    {
        return SyncRun::query()
            ->select(['id', 'mode', 'resources_filter', 'started_at', 'finished_at', 'status', 'mutations_applied', 'snapshots_fetched', 'rows_upserted', 'rows_deleted', 'rows_skipped_locked', 'triggered_by'])
            ->latest('started_at')
            ->limit($limit)
            ->get();
    }

    public function states(): Collection
    {
        return SyncState::query()
            ->select(['id', 'resources_filter', 'last_sequence', 'last_run_at', 'status', 'last_error'])
            ->latest('last_run_at')
            ->get();
    }

    public function findRun(int $id): ?SyncRun
    {
        return SyncRun::query()->find($id);
    }

    public function latestRunFor(string $filter): ?SyncRun
    {
        return SyncRun::query()->where('resources_filter', $filter)->latest('id')->first();
    }
}
