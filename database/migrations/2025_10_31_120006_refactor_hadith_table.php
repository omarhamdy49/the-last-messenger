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
     */
    public function up(): void
    {
        Schema::table('hadith', function (Blueprint $table) {
            // Remove narrator_chain text column if it exists
            if (Schema::hasColumn('hadith', 'narrator_chain')) {
                $table->dropColumn('narrator_chain');
            }

            // Remove external_id if it exists
            if (Schema::hasColumn('hadith', 'external_id')) {
                $table->dropUnique(['external_id']);
                $table->dropColumn('external_id');
            }

            // Remove reference_url if it exists
            if (Schema::hasColumn('hadith', 'reference_url')) {
                $table->dropColumn('reference_url');
            }

            // Add active flag
            $table->boolean('active')->default(true)->after('grade');

            // Add searchable_ar generated column (for Arabic search)
            if (config('database.default') === 'mysql') {
                DB::statement("ALTER TABLE hadith ADD COLUMN searchable_ar TEXT GENERATED ALWAYS AS (arabic_text) STORED");
                DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_ar (searchable_ar)');
            }
        });

        // Rename translation_en_searchable to searchable_en if it exists
        Schema::table('hadith', function (Blueprint $table) {
            if (Schema::hasColumn('hadith', 'translation_en_searchable')) {
                if (config('database.default') === 'mysql') {
                    // Drop the old fulltext index first
                    DB::statement('ALTER TABLE hadith DROP INDEX fulltext_translation_en');

                    // Drop the old generated column
                    DB::statement('ALTER TABLE hadith DROP COLUMN translation_en_searchable');

                    // Add the new generated column with new name
                    DB::statement("ALTER TABLE hadith ADD COLUMN searchable_en TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED");

                    // Create new fulltext index
                    DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_en (searchable_en)');
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hadith', function (Blueprint $table) {
            // Remove active flag
            if (Schema::hasColumn('hadith', 'active')) {
                $table->dropColumn('active');
            }

            // Remove searchable columns and indexes
            if (config('database.default') === 'mysql') {
                if (Schema::hasColumn('hadith', 'searchable_ar')) {
                    DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_ar');
                    DB::statement('ALTER TABLE hadith DROP COLUMN searchable_ar');
                }

                if (Schema::hasColumn('hadith', 'searchable_en')) {
                    DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_en');
                    DB::statement('ALTER TABLE hadith DROP COLUMN searchable_en');

                    // Re-add old translation_en_searchable
                    DB::statement("ALTER TABLE hadith ADD COLUMN translation_en_searchable TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED");
                    DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_translation_en (translation_en_searchable)');
                }
            }

            // Re-add removed columns
            $table->text('narrator_chain')->nullable()->after('arabic_text');
            $table->string('reference_url')->nullable()->after('grade');
            $table->string('external_id')->nullable()->after('id');

            if (Schema::hasColumn('hadith', 'external_id')) {
                $table->unique('external_id');
            }
        });
    }
};
