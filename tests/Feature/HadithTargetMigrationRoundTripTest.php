<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class HadithTargetMigrationRoundTripTest extends TestCase
{
    public function test_hadith_rebuild_migration_round_trips_on_mysql(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            $this->markTestSkipped('MySQL migration round-trip verification.');
        }

        /** @var Migration $migration */
        $migration = require database_path('migrations/2026_07_29_000001_rebuild_hadith_schema.php');
        /** @var Migration $restoredTables */
        $restoredTables = require database_path(
            'migrations/2026_07_29_000002_restore_hadith_applications_and_fiqh_rulings.php',
        );

        try {
            $restoredTables->down();
            $migration->down();

            $this->assertTrue(Schema::hasTable('hadith_companions'));
            $this->assertTrue(Schema::hasTable('hadith_grading_levels'));
            $this->assertTrue(Schema::hasColumn('hadith_hadiths', 'book_id'));
            $this->assertTrue(Schema::hasColumn('hadith_translations', 'language_code'));
        } finally {
            $migration->up();
            $restoredTables->up();
        }

        $this->assertFalse(Schema::hasTable('hadith_companions'));
        $this->assertFalse(Schema::hasTable('hadith_grading_levels'));
        $this->assertTrue(Schema::hasTable('hadith_applications'));
        $this->assertTrue(Schema::hasTable('hadith_fiqh_rulings'));
        $this->assertFalse(Schema::hasColumn('hadith_hadiths', 'book_id'));
        $this->assertTrue(Schema::hasColumn('hadith_translations', 'search_text'));
    }
}
