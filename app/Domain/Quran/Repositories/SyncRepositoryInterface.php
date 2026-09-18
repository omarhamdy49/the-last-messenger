<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use App\Domain\Quran\Enums\ResourceKind;
use App\Domain\Quran\Enums\SyncRunMode;
use App\Domain\Quran\Models\SyncRun;
use App\Domain\Quran\Models\SyncState;
use Illuminate\Support\Collection;

/**
 * Sync bookkeeping (states, runs) and the resource lookups the sync needs.
 */
interface SyncRepositoryInterface
{
    public function stateFor(string $filter): SyncState;

    public function markRunning(SyncState $state): void;

    public function markSucceeded(SyncState $state, string $token, int $sequence): void;

    public function markFailed(SyncState $state, string $error): void;

    public function startRun(SyncRunMode $mode, ?string $filter, ?int $userId): SyncRun;

    /**
     * Enabled QF resources that sync can carry: [kind value => sorted qf ids].
     *
     * @return array<string, list<int>>
     */
    public function syncEnabledQfIds(): array;

    /**
     * @return list<int>
     */
    public function activeMushafIds(): array;

    /**
     * Local id of the QF resource of this kind, or null when it is not imported.
     */
    public function resourceId(ResourceKind $kind, int $qfId): ?int;

    public function touchResource(int $resourceId, string $table): void;

    public function deactivateResource(int $resourceId, ?string $reason): void;

    public function touchMushaf(int $mushafId): void;

    public function deactivateMushaf(int $mushafId, ?string $reason): void;

    /**
     * @return Collection<int, SyncRun>
     */
    /**
     * When Quran Foundation content was last synced successfully, for the dashboard.
     */
    public function lastSucceededAt(): ?string;

    public function recentRuns(int $limit): Collection;

    /**
     * @return Collection<int, SyncState>
     */
    public function states(): Collection;

    public function findRun(int $id): ?SyncRun;

    public function latestRunFor(string $filter): ?SyncRun;
}
