<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Drop JSON translatable columns from seerah_events.
 *
 * The sahaba half of this migration was removed with the Sahaba module: the
 * table no longer exists, so dropping columns from it would fail on a fresh
 * build. Seerah survives and still needs its JSON columns removed in favour of
 * seerah_event_translations.
 *
 * These columns are now stored in seerah_event_translations and sahabi_translations.
 * Only run this after verifying data migration succeeded (migration 200002).
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop only what is actually there. Production reached this migration
        // with the columns already gone, and an unconditional drop aborts the
        // whole deploy on `1091 Can't DROP 'title'`. A fresh build still has
        // all three (see the create migration), so nothing is skipped there.
        $columns = array_values(array_filter(
            ['title', 'description', 'content'],
            fn (string $column): bool => Schema::hasColumn('seerah_events', $column),
        ));

        if ($columns === []) {
            return;
        }

        Schema::table('seerah_events', function (Blueprint $table) use ($columns) {
            $table->dropColumn($columns);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Mirror of up(): re-add only what is missing, so a rollback after the
        // no-op path above does not try to create a column that still exists.
        Schema::table('seerah_events', function (Blueprint $table) {
            if (! Schema::hasColumn('seerah_events', 'title')) {
                $table->json('title')->nullable()->after('slug');
            }
            if (! Schema::hasColumn('seerah_events', 'description')) {
                $table->json('description')->nullable()->after('title');
            }
            if (! Schema::hasColumn('seerah_events', 'content')) {
                $table->json('content')->nullable()->after('description');
            }
        });
    }
};
