<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

/**
 * Bulk writes for the QF importers and sync. All multi-row writes run in
 * transactions here (constitution I), in chunks, and report reconciled counts.
 */
interface ImportRepositoryInterface
{
    /**
     * Upsert rows into a table.
     *
     * Counts are computed against what is stored: a row whose values already match
     * is `unchanged`, an existing row with different values is `updated`, a missing
     * row is `written`. When `$lockColumn` is given, existing rows with that column
     * true are left untouched and counted as `skipped_locked`.
     *
     * @param  list<array<string, mixed>>  $rows  every row must carry the $uniqueBy columns
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $updateColumns  columns overwritten on existing rows
     * @return array{written: int, updated: int, unchanged: int, skipped_locked: int}
     */
    public function upsert(string $table, array $rows, array $uniqueBy, array $updateColumns, ?string $lockColumn = null): array;

    /**
     * Replace every row of one resource (a sync snapshot): delete the resource's
     * unlocked rows that are not in `$rows`, then upsert `$rows` respecting locks.
     * One transaction.
     *
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $updateColumns
     * @return array{written: int, updated: int, unchanged: int, skipped_locked: int, deleted: int}
     */
    public function replaceForOwner(string $table, string $ownerColumn, int $ownerId, array $rows, array $uniqueBy, array $updateColumns, ?string $lockColumn = null): array;

    public function count(string $table, array $where = []): int;

    /**
     * @param  list<int|string>  $values
     * @return array<int|string, int> value => id
     */
    public function idsBy(string $table, string $column, array $values): array;
}
