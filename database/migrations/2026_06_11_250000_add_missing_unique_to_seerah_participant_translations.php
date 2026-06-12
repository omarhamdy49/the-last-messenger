<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/*
 * The phase-4 migration's unique on (seerah_participant_id, locale) silently never
 * applied to environments where the table pre-existed (the create was hasTable-guarded,
 * and the auto-generated 68-char index name exceeded MySQL's 64-char limit anyway).
 * Fresh databases get the constraint from the now-fixed create; this migration aligns
 * existing databases. Live data verified duplicate-free before adding.
 */
return new class extends Migration
{
    private const INDEX_NAME = 'spt_participant_locale_unique';

    public function up(): void
    {
        if ($this->indexExists()) {
            return;
        }

        Schema::table('seerah_participant_translations', function (Blueprint $table) {
            $table->unique(['seerah_participant_id', 'locale'], self::INDEX_NAME);
        });
    }

    public function down(): void
    {
        if (! $this->indexExists()) {
            return;
        }

        Schema::table('seerah_participant_translations', function (Blueprint $table) {
            $table->dropUnique(self::INDEX_NAME);
        });
    }

    private function indexExists(): bool
    {
        if (DB::getDriverName() === 'mysql') {
            return DB::table('information_schema.STATISTICS')
                ->where('TABLE_SCHEMA', DB::getDatabaseName())
                ->where('TABLE_NAME', 'seerah_participant_translations')
                ->where('INDEX_NAME', self::INDEX_NAME)
                ->exists();
        }

        // SQLite (tests/fresh) gets the constraint from the fixed create migration.
        $indexes = DB::select("SELECT name FROM sqlite_master WHERE type = 'index' AND tbl_name = 'seerah_participant_translations'");

        return collect($indexes)->pluck('name')->contains(self::INDEX_NAME);
    }
};
