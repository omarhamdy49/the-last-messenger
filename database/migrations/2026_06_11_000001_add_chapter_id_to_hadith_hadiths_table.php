<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Adds the missing hadith → chapter linkage column. Nullable-first because
     * hadith_hadiths holds 50k+ rows; the value is backfilled by the importer
     * (islamic-data:import-hadith), not by this migration.
     */
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table) {
            // ->after() is honoured on MySQL and ignored by the SQLite test driver.
            $table->unsignedBigInteger('chapter_id')
                ->nullable()
                ->after('book_id')
                ->comment('Reference to hadith_chapters (باب) — nullable, backfilled by import');

            $table->index('chapter_id', 'hadith_hadiths_chapter_id_index');
        });

        // SQLite cannot add a foreign key to an existing table via ALTER;
        // the FK is a MySQL-only integrity constraint.
        if (DB::getDriverName() === 'mysql') {
            Schema::table('hadith_hadiths', function (Blueprint $table) {
                $table->foreign('chapter_id', 'hadith_hadiths_chapter_id_foreign')
                    ->references('id')
                    ->on('hadith_chapters')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('hadith_hadiths', function (Blueprint $table) {
                $table->dropForeign('hadith_hadiths_chapter_id_foreign');
            });
        }

        Schema::table('hadith_hadiths', function (Blueprint $table) {
            $table->dropIndex('hadith_hadiths_chapter_id_index');
            $table->dropColumn('chapter_id');
        });
    }
};
