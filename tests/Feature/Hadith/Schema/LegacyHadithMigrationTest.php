<?php

declare(strict_types=1);

namespace Tests\Feature\Hadith\Schema;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LegacyHadithMigrationTest extends TestCase
{
    public function test_the_legacy_schema_is_archived_before_the_rebuild(): void
    {
        $original = DB::getDefaultConnection();
        config()->set('database.connections.hadith_migration_test', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
            'foreign_key_constraints' => true,
        ]);
        DB::setDefaultConnection('hadith_migration_test');

        try {
            Schema::create('hadith_collections', function (Blueprint $table): void {
                $table->id();
                $table->string('name_ar');
                $table->string('name_en');
            });

            (require database_path('migrations/2026_07_31_000000_prepare_existing_hadith_schema.php'))->up();
            (require database_path('migrations/2026_07_31_000001_create_hadith_collections_table.php'))->up();

            $this->assertTrue(Schema::hasTable('legacy_hadith_collections'));
            $this->assertTrue(Schema::hasColumns('hadith_collections', ['code', 'name', 'is_published']));
        } finally {
            DB::purge('hadith_migration_test');
            DB::setDefaultConnection($original);
        }
    }
}
