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
        // Articles - Improve existing full-text search (only if indexes don't exist)
        if (Schema::hasTable('articles')) {
            try {
                DB::statement('CREATE INDEX idx_articles_status ON articles(status)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_articles_published_at ON articles(published_at)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }

        // Hadith - Add search indexes
        if (Schema::hasTable('hadith')) {
            try {
                DB::statement('CREATE INDEX idx_hadith_book_id ON hadith(book_id)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            // chapter_id did not exist when this migration originally ran; guard on the
            // column so migrate:fresh succeeds regardless of ordering. The hadith_hadiths
            // chapter_id index is created (with a distinct name) by the
            // 2026_06_11 add_chapter_id_to_hadith_hadiths_table migration.
            if (Schema::hasColumn('hadith', 'chapter_id')) {
                try {
                    DB::statement('CREATE INDEX idx_hadith_chapter_id ON hadith(chapter_id)');
                } catch (Exception $e) {
                    // Index might already exist, skip
                }
            }
            try {
                DB::statement('CREATE INDEX idx_hadith_authenticity ON hadith(authenticity_grade)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }

        // Media Programs - Add search indexes
        if (Schema::hasTable('media_programs')) {
            try {
                DB::statement('CREATE INDEX idx_media_programs_category ON media_programs(category_id)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_media_programs_type ON media_programs(type)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }

        // Seerah Events - Add search indexes
        if (Schema::hasTable('seerah_events')) {
            try {
                DB::statement('CREATE INDEX idx_seerah_events_type ON seerah_events(event_type)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_seerah_events_importance ON seerah_events(importance_level)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_seerah_events_hijri_date ON seerah_events(hijri_date)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }

        // Sahaba - Add search indexes
        if (Schema::hasTable('sahabis')) {
            try {
                DB::statement('CREATE INDEX idx_sahabis_status ON sahabis(status)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_sahabis_tribe ON sahabis(tribe)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }

        // Quran Verses - Add search indexes
        if (Schema::hasTable('quran_verses')) {
            try {
                DB::statement('CREATE INDEX idx_quran_verses_surah ON quran_verses(surah_id)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_quran_verses_juz ON quran_verses(juz_number)');
            } catch (Exception $e) {
                // Index might already exist, skip
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop indexes if they exist
        if (Schema::hasTable('articles')) {
            try {
                DB::statement('DROP INDEX idx_articles_status ON articles');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_articles_published_at ON articles');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }

        if (Schema::hasTable('hadith')) {
            try {
                DB::statement('DROP INDEX idx_hadith_book_id ON hadith');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_hadith_chapter_id ON hadith');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_hadith_authenticity ON hadith');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }

        if (Schema::hasTable('media_programs')) {
            try {
                DB::statement('DROP INDEX idx_media_programs_category ON media_programs');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_media_programs_type ON media_programs');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }

        if (Schema::hasTable('seerah_events')) {
            try {
                DB::statement('DROP INDEX idx_seerah_events_type ON seerah_events');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_seerah_events_importance ON seerah_events');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_seerah_events_hijri_date ON seerah_events');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }

        if (Schema::hasTable('sahabis')) {
            try {
                DB::statement('DROP INDEX idx_sahabis_status ON sahabis');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_sahabis_tribe ON sahabis');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }

        if (Schema::hasTable('quran_verses')) {
            try {
                DB::statement('DROP INDEX idx_quran_verses_surah ON quran_verses');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
            try {
                DB::statement('DROP INDEX idx_quran_verses_juz ON quran_verses');
            } catch (Exception $e) {
                // Index might not exist, skip
            }
        }
    }
};
