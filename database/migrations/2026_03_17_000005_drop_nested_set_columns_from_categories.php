<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Drop lft/rgt nested-set columns from categories.
 *
 * Background
 * ----------
 * Migration 2025_11_07_200000_enhance_categories_table added lft, rgt, depth
 * intending to implement the nested-set model.  No nested-set package was ever
 * installed (composer.json has no lazychaser/laravel-nestedset or equivalent),
 * so lft and rgt were never populated.  Every row has lft=0 and rgt=0, which
 * means:
 *   - Category::ancestors() always returns an empty result set (WHERE lft < 0
 *     never matches).
 *   - Category::isLeaf() always returns false (0 - 0 = 0, not 1).
 *   - getBreadcrumb() only ever returns the leaf node itself.
 *
 * depth is also always 0 but IS read in several places (API resource, admin
 * views, scopeWithDepth).  It is kept and backfilled from parent_id in this
 * migration so that existing rows get a correct value immediately.
 *
 * Rollback safety
 * ---------------
 * The down() method restores lft and rgt with default 0 (the same useless
 * state they were in before this migration, so a rollback is safe).
 */
return new class extends Migration
{
    public function up(): void
    {
        // ------------------------------------------------------------------
        // 1. Backfill depth for all existing rows using parent_id traversal.
        //    We do this before touching any schema so MySQL is happy.
        // ------------------------------------------------------------------
        $this->backfillDepth();

        // ------------------------------------------------------------------
        // 2. Drop the lft/rgt index and columns.
        // ------------------------------------------------------------------
        Schema::table('categories', function (Blueprint $table) {
            // Drop the composite index added in enhance_categories_table.
            // Laravel names it <table>_<col1>_<col2>_index by default.
            $table->dropIndex(['lft', 'rgt']);

            // Drop the separate depth index as well; depth itself stays but
            // the index was only useful for nested-set range queries.
            $table->dropIndex(['depth']);

            $table->dropColumn(['lft', 'rgt']);
        });
    }

    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Restore columns with the same defaults they had originally.
            // They will be all-zero again — identical to the pre-drop state.
            $table->unsignedInteger('lft')->default(0)->after('parent_id');
            $table->unsignedInteger('rgt')->default(0)->after('lft');

            $table->index(['lft', 'rgt']);
            $table->index(['depth']);
        });
    }

    // ------------------------------------------------------------------
    // Helpers
    // ------------------------------------------------------------------

    /**
     * Compute and persist the correct depth for every category row.
     *
     * Depth is defined as the number of ancestors (root = 0).
     * We walk up via parent_id, which is already reliable.
     * The iteration cap of 50 prevents an infinite loop if the data
     * somehow contains a circular reference.
     */
    private function backfillDepth(): void
    {
        $rows = DB::table('categories')
            ->whereNull('deleted_at')
            ->orderBy('id')
            ->get(['id', 'parent_id']);

        // Build a quick id -> parent_id lookup to avoid per-row queries.
        $parentMap = [];
        foreach ($rows as $row) {
            $parentMap[$row->id] = $row->parent_id;
        }

        foreach ($rows as $row) {
            $depth = 0;
            $current = $row->parent_id;
            $visited = [];

            while ($current !== null && ! in_array($current, $visited, true)) {
                $visited[] = $current;
                $depth++;
                $current = $parentMap[$current] ?? null;

                if ($depth > 50) {
                    // Circular reference guard — leave depth as-is for this row.
                    $depth = 0;
                    break;
                }
            }

            if ($depth !== 0) {
                DB::table('categories')
                    ->where('id', $row->id)
                    ->update(['depth' => $depth]);
            }
        }
    }
};
