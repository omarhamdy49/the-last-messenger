<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Recreated migration: this file was lost after it ran in production, leaving fresh
 * databases (e.g. the sqlite test driver) with the legacy NOT NULL JSON columns that
 * blog translations moved out of (series/category/tag *_translations tables).
 *
 * Guarded with hasColumn checks so it is idempotent on databases where it already ran.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->dropColumnsIfExist('series', ['title', 'description']);
        $this->dropColumnsIfExist('categories', ['name', 'description']);
        $this->dropColumnsIfExist('tags', ['name', 'description']);
    }

    public function down(): void
    {
        $this->restoreJsonColumnsIfMissing('series', ['title', 'description']);
        $this->restoreJsonColumnsIfMissing('categories', ['name', 'description']);
        $this->restoreJsonColumnsIfMissing('tags', ['name', 'description']);
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function dropColumnsIfExist(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $columnsToRemove = array_values(array_intersect($columns, Schema::getColumnListing($table)));

        if ($columnsToRemove === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columnsToRemove) {
            $blueprint->dropColumn($columnsToRemove);
        });
    }

    /**
     * @param  array<int, string>  $columns
     */
    private function restoreJsonColumnsIfMissing(string $table, array $columns): void
    {
        if (! Schema::hasTable($table)) {
            return;
        }

        $existing = Schema::getColumnListing($table);
        $columnsToRestore = array_values(array_diff($columns, $existing));

        if ($columnsToRestore === []) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columnsToRestore) {
            foreach ($columnsToRestore as $column) {
                $blueprint->json($column)->nullable();
            }
        });
    }
};
