<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (config('database.default') === 'mysql') {
            // Drop existing searchable columns and their fulltext indexes
            if (Schema::hasColumn('hadith', 'searchable_ar')) {
                DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_ar');
                DB::statement('ALTER TABLE hadith DROP COLUMN searchable_ar');
            }

            if (Schema::hasColumn('hadith', 'searchable_en')) {
                DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_en');
                DB::statement('ALTER TABLE hadith DROP COLUMN searchable_en');
            }

            // Create searchable_ar with comprehensive text cleaning
            // Removes:
            // 1. HTML tags: <[^>]+>
            // 2. Square brackets: \\[|\\]
            // 3. Arabic tashkeel (diacritical marks):
            //    - U+064B to U+065F (fatha, damma, kasra, sukun, shadda, tanween, etc.)
            //    - U+0670 (superscript alef)
            //    - U+06D6 to U+06ED (various Arabic marks)
            // 4. Special annotation tags: \[narrator[^\]]*\]|\[\/narrator\]|\[prematn\]|\[\/prematn\]|\[matn\]|\[\/matn\]
            // 5. Extra whitespace
            DB::statement("
                ALTER TABLE hadith ADD COLUMN searchable_ar TEXT
                GENERATED ALWAYS AS (
                    TRIM(
                        REGEXP_REPLACE(
                            REGEXP_REPLACE(
                                REGEXP_REPLACE(
                                    REGEXP_REPLACE(
                                        REGEXP_REPLACE(
                                            arabic_text,
                                            '<[^>]+>', ''
                                        ),
                                        '\\\\[narrator[^\\\\]]*\\\\]|\\\\[\\\\/narrator\\\\]|\\\\[prematn\\\\]|\\\\[\\\\/prematn\\\\]|\\\\[matn\\\\]|\\\\[\\\\/matn\\\\]', ''
                                    ),
                                    '\\\\[|\\\\]', ''
                                ),
                                '[\\u064B-\\u065F\\u0670\\u06D6-\\u06ED]', ''
                            ),
                            '[[:space:]]+', ' '
                        )
                    )
                ) STORED
            ");

            // Create searchable_en with comprehensive text cleaning
            // Removes:
            // 1. HTML tags: <[^>]+>
            // 2. Square brackets: \\[|\\]
            // 3. Special characters except letters, numbers, spaces, and basic punctuation
            // 4. Extra whitespace
            DB::statement("
                ALTER TABLE hadith ADD COLUMN searchable_en TEXT
                GENERATED ALWAYS AS (
                    TRIM(
                        REGEXP_REPLACE(
                            REGEXP_REPLACE(
                                REGEXP_REPLACE(
                                    REGEXP_REPLACE(
                                        JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en')),
                                        '<[^>]+>', ''
                                    ),
                                    '\\\\[narrator[^\\\\]]*\\\\]|\\\\[\\\\/narrator\\\\]|\\\\[prematn\\\\]|\\\\[\\\\/prematn\\\\]|\\\\[matn\\\\]|\\\\[\\\\/matn\\\\]', ''
                                ),
                                '\\\\[|\\\\]', ''
                            ),
                            '[[:space:]]+', ' '
                        )
                    )
                ) STORED
            ");

            // Recreate fulltext indexes
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_ar (searchable_ar)');
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_en (searchable_en)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (config('database.default') === 'mysql') {
            // Drop new searchable columns
            if (Schema::hasColumn('hadith', 'searchable_ar')) {
                DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_ar');
                DB::statement('ALTER TABLE hadith DROP COLUMN searchable_ar');
            }

            if (Schema::hasColumn('hadith', 'searchable_en')) {
                DB::statement('ALTER TABLE hadith DROP INDEX fulltext_searchable_en');
                DB::statement('ALTER TABLE hadith DROP COLUMN searchable_en');
            }

            // Restore original searchable columns (without cleaning)
            DB::statement('ALTER TABLE hadith ADD COLUMN searchable_ar TEXT GENERATED ALWAYS AS (arabic_text) STORED');
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_ar (searchable_ar)');

            DB::statement("ALTER TABLE hadith ADD COLUMN searchable_en TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED");
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_searchable_en (searchable_en)');
        }
    }
};
