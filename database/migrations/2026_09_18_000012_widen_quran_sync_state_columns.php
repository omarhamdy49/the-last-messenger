<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Both of these columns grow with the number of enabled resources, so a fixed length was
 * always a ceiling on how much of the catalogue an editor may switch on.
 *
 * `sync_token` was VARCHAR(500). Quran Foundation's token encodes the canonical resource
 * filter, so with 176 resources enabled it passed 1,400 characters and the run died
 * *after* applying its changes, with "Data too long for column 'sync_token'" — the worst
 * possible moment, because the next run then had no token to resume from.
 *
 * `resources_filter` was VARCHAR(1000) and already stood at 769. Uniqueness lives on
 * `filter_hash`, not on the filter text, so neither column is indexed and both can be TEXT.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quran_sync_states') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE quran_sync_states MODIFY sync_token TEXT NULL');
        DB::statement('ALTER TABLE quran_sync_states MODIFY resources_filter TEXT NOT NULL');
        DB::statement('ALTER TABLE quran_sync_runs MODIFY resources_filter TEXT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('quran_sync_states') || DB::getDriverName() !== 'mysql') {
            return;
        }

        // A stored token longer than the old cap would be truncated into something the
        // API would reject, which is harder to diagnose than a failed rollback.
        $longest = (int) DB::table('quran_sync_states')->selectRaw('MAX(LENGTH(sync_token)) AS n')->value('n');

        if ($longest > 500) {
            throw new RuntimeException("Cannot narrow quran_sync_states.sync_token: a row holds {$longest} bytes.");
        }

        DB::statement('ALTER TABLE quran_sync_states MODIFY sync_token VARCHAR(500) NULL');
        DB::statement('ALTER TABLE quran_sync_states MODIFY resources_filter VARCHAR(1000) NOT NULL');
        DB::statement('ALTER TABLE quran_sync_runs MODIFY resources_filter VARCHAR(1000) NULL');
    }
};
