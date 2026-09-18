<?php

declare(strict_types=1);

namespace App\Domain\Quran\Repositories;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

final class EloquentImportRepository implements ImportRepositoryInterface
{
    private const CHUNK = 500;

    public function upsert(string $table, array $rows, array $uniqueBy, array $updateColumns, ?string $lockColumn = null): array
    {
        $totals = ['written' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped_locked' => 0];

        foreach (array_chunk($rows, self::CHUNK) as $chunk) {
            $counts = DB::transaction(fn (): array => $this->upsertChunk($table, $chunk, $uniqueBy, $updateColumns, $lockColumn));
            foreach ($totals as $key => $value) {
                $totals[$key] = $value + $counts[$key];
            }
        }

        return $totals;
    }

    public function replaceForOwner(string $table, string $ownerColumn, int $ownerId, array $rows, array $uniqueBy, array $updateColumns, ?string $lockColumn = null): array
    {
        return DB::transaction(function () use ($table, $ownerColumn, $ownerId, $rows, $uniqueBy, $updateColumns, $lockColumn): array {
            $incoming = [];
            foreach ($rows as $row) {
                $incoming[$this->keyOf($row, $uniqueBy)] = true;
            }

            // Delete this owner's rows that the snapshot no longer contains. Locked
            // rows are editorial overrides and stay.
            $deleted = 0;
            $existing = DB::table($table)
                ->select(array_values(array_unique([...$uniqueBy, 'id'])))
                ->where($ownerColumn, $ownerId)
                ->when($lockColumn !== null, fn (Builder $q) => $q->where($lockColumn, false))
                ->orderBy('id')
                ->get();

            $staleIds = [];
            foreach ($existing as $record) {
                if (! isset($incoming[$this->keyOf((array) $record, $uniqueBy)])) {
                    $staleIds[] = $record->id;
                }
            }
            foreach (array_chunk($staleIds, self::CHUNK) as $ids) {
                $deleted += DB::table($table)->whereIn('id', $ids)->delete();
            }

            $counts = ['written' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped_locked' => 0];
            foreach (array_chunk($rows, self::CHUNK) as $chunk) {
                foreach ($this->upsertChunk($table, $chunk, $uniqueBy, $updateColumns, $lockColumn) as $key => $value) {
                    $counts[$key] += $value;
                }
            }

            return $counts + ['deleted' => $deleted];
        });
    }

    public function count(string $table, array $where = []): int
    {
        return DB::table($table)->where($where)->count();
    }

    public function idsBy(string $table, string $column, array $values): array
    {
        $map = [];
        foreach (array_chunk(array_values(array_unique($values)), 1000) as $chunk) {
            foreach (DB::table($table)->select(['id', $column])->whereIn($column, $chunk)->orderBy('id')->get() as $row) {
                $map[$row->{$column}] = (int) $row->id;
            }
        }

        return $map;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $updateColumns
     * @return array{written: int, updated: int, unchanged: int, skipped_locked: int}
     */
    private function upsertChunk(string $table, array $rows, array $uniqueBy, array $updateColumns, ?string $lockColumn): array
    {
        $counts = ['written' => 0, 'updated' => 0, 'unchanged' => 0, 'skipped_locked' => 0];
        if ($rows === []) {
            return $counts;
        }

        $columns = array_values(array_unique([...$uniqueBy, ...$updateColumns, ...($lockColumn !== null ? [$lockColumn] : [])]));
        $existing = [];
        foreach ($this->existingRows($table, $rows, $uniqueBy, $columns) as $record) {
            $existing[$this->keyOf($record, $uniqueBy)] = $record;
        }

        $now = now();
        $toWrite = [];
        foreach ($rows as $row) {
            $key = $this->keyOf($row, $uniqueBy);
            $current = $existing[$key] ?? null;

            if ($current === null) {
                $counts['written']++;
                $toWrite[] = $row + ['created_at' => $now, 'updated_at' => $now];

                continue;
            }

            if ($lockColumn !== null && (bool) $current[$lockColumn]) {
                $counts['skipped_locked']++;

                continue;
            }

            if ($this->sameValues($current, $row, $updateColumns)) {
                $counts['unchanged']++;

                continue;
            }

            $counts['updated']++;
            $toWrite[] = $row + ['created_at' => $now, 'updated_at' => $now];
        }

        if ($toWrite !== []) {
            // One statement for inserts and updates; created_at is not in the update
            // list, so existing rows keep theirs.
            DB::table($table)->upsert($this->uniformRows($toWrite), $uniqueBy, [...$updateColumns, 'updated_at']);
        }

        return $counts;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     * @param  list<string>  $uniqueBy
     * @param  list<string>  $columns
     * @return list<array<string, mixed>>
     */
    private function existingRows(string $table, array $rows, array $uniqueBy, array $columns): array
    {
        $query = DB::table($table)->select($columns);

        if (count($uniqueBy) === 1) {
            $column = $uniqueBy[0];
            $query->whereIn($column, array_values(array_unique(array_column($rows, $column))));
        } else {
            // Row-constructor IN keeps the lookup to one indexed query per chunk.
            $placeholders = '('.implode(',', array_fill(0, count($uniqueBy), '?')).')';
            $bindings = [];
            foreach ($rows as $row) {
                foreach ($uniqueBy as $column) {
                    $bindings[] = $row[$column];
                }
            }
            $query->whereRaw(
                '('.implode(',', $uniqueBy).') IN ('.implode(',', array_fill(0, count($rows), $placeholders)).')',
                $bindings,
            );
        }

        return array_map(static fn (object $r): array => (array) $r, $query->get()->all());
    }

    /**
     * Every row in one upsert statement must have the same columns.
     *
     * @param  list<array<string, mixed>>  $rows
     * @return list<array<string, mixed>>
     */
    private function uniformRows(array $rows): array
    {
        $allColumns = [];
        foreach ($rows as $row) {
            foreach (array_keys($row) as $column) {
                $allColumns[$column] = null;
            }
        }

        return array_map(static fn (array $row): array => array_merge($allColumns, $row), $rows);
    }

    /**
     * @param  array<string, mixed>  $row
     * @param  list<string>  $uniqueBy
     */
    private function keyOf(array $row, array $uniqueBy): string
    {
        $parts = [];
        foreach ($uniqueBy as $column) {
            $parts[] = (string) ($row[$column] ?? '');
        }

        return implode("\x1f", $parts);
    }

    /**
     * @param  array<string, mixed>  $current
     * @param  array<string, mixed>  $incoming
     * @param  list<string>  $columns
     */
    private function sameValues(array $current, array $incoming, array $columns): bool
    {
        foreach ($columns as $column) {
            if (! array_key_exists($column, $incoming)) {
                continue;
            }
            if ($this->normalize($current[$column] ?? null) !== $this->normalize($incoming[$column])) {
                return false;
            }
        }

        return true;
    }

    private function normalize(mixed $value): mixed
    {
        if ($value === null) {
            return null;
        }
        if (is_bool($value)) {
            return $value ? '1' : '0';
        }
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        $string = (string) $value;
        // JSON columns come back re-serialised by MySQL; compare them structurally.
        if ($string !== '' && ($string[0] === '{' || $string[0] === '[')) {
            $decoded = json_decode($string, true);
            if (json_last_error() === JSON_ERROR_NONE) {
                return json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
        }

        return $string;
    }
}
