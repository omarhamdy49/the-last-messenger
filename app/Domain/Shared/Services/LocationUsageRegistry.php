<?php

declare(strict_types=1);

namespace App\Domain\Shared\Services;

use Illuminate\Support\Facades\DB;

/**
 * Which tables reference `locations`, and how many rows each holds.
 *
 * ------------------------------------------------------------------------
 * WHY THIS EXISTS AT ALL.
 *
 * The obvious implementation is `hasMany(SeerahEvent)`, `hasMany(Muslim)` and
 * `hasMany(Surah)` on the Location model. That would make `Shared` import three
 * domain modules and break the leaf invariant constitution IV calls "the
 * invariant that actually protects module isolation" — `composer deptrac` would
 * fail, and the baseline may never be used to admit a new violation.
 *
 * So the dependency arrow is inverted. Each consuming module's service provider
 * tells Shared that a column exists; Shared never names the module. Counting
 * runs through the query builder on a table name, which needs no model class.
 *
 * The alternative of hard-coding the three table names inside LocationService
 * would also pass deptrac, but it hides a real coupling from the reader and
 * silently misses a fourth consumer added later. This mirrors the existing
 * PublicVisibilityRegistry pattern.
 *
 * See specs/003-locations-module/research.md R-06.
 * ------------------------------------------------------------------------
 */
class LocationUsageRegistry
{
    /**
     * @var array<string, array{table: string, column: string, label: string}>
     */
    private array $references = [];

    /**
     * @param  array<int, array{table: string, column: string, label?: string}>  $references
     */
    public function __construct(array $references = [])
    {
        foreach ($references as $reference) {
            $this->register(
                $reference['table'],
                $reference['column'],
                $reference['label'] ?? $reference['table']
            );
        }
    }

    /**
     * Table and column names come from application code, never from user input —
     * they are interpolated into query structure, so a user-supplied value here
     * would be injection (constitution VII).
     */
    public function register(string $table, string $column, ?string $label = null): void
    {
        $key = "{$table}.{$column}";

        $this->references[$key] = [
            'table' => $table,
            'column' => $column,
            'label' => $label ?? $table,
        ];
    }

    /**
     * @return array<int, array{table: string, column: string, label: string}>
     */
    public function all(): array
    {
        return array_values($this->references);
    }

    /**
     * Per-table reference counts for one location. Only non-zero entries are
     * returned, so an unreferenced location yields an empty array.
     *
     * @return array<string, int> table => count
     */
    public function countReferences(int $locationId): array
    {
        $counts = [];

        foreach ($this->references as $reference) {
            $count = DB::table($reference['table'])
                ->where($reference['column'], $locationId)
                ->count();

            if ($count > 0) {
                $counts[$reference['table']] = $count;
            }
        }

        return $counts;
    }

    public function isReferenced(int $locationId): bool
    {
        foreach ($this->references as $reference) {
            $exists = DB::table($reference['table'])
                ->where($reference['column'], $locationId)
                ->exists();

            if ($exists) {
                return true;
            }
        }

        return false;
    }

    /**
     * Rows that name a place in text but carry no foreign key — the editor's
     * review queue (FR-020). Returns the count per registered table.
     *
     * @param  array<string, string>  $textColumns  table => the free-text column to test
     * @return array<string, int>
     */
    public function countUnresolved(array $textColumns): array
    {
        $counts = [];

        foreach ($this->references as $reference) {
            $textColumn = $textColumns[$reference['table']] ?? null;

            if ($textColumn === null) {
                continue;
            }

            $counts[$reference['table']] = DB::table($reference['table'])
                ->whereNull($reference['column'])
                ->whereNotNull($textColumn)
                ->where($textColumn, '<>', '')
                ->count();
        }

        return $counts;
    }
}
