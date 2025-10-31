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
        // Articles - Improve existing full-text search (only if indexes don't exist)
        if (Schema::hasTable('articles')) {
            try {
                DB::statement('CREATE INDEX idx_articles_status ON articles(status)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_articles_published_at ON articles(published_at)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Hadith - Add search indexes
        if (Schema::hasTable('hadith')) {
            try {
                DB::statement('CREATE INDEX idx_hadith_book_id ON hadith(book_id)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_hadith_chapter_id ON hadith(chapter_id)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_hadith_authenticity ON hadith(authenticity_grade)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Media Programs - Add search indexes
        if (Schema::hasTable('media_programs')) {
            try {
                DB::statement('CREATE INDEX idx_media_programs_category ON media_programs(category_id)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_media_programs_type ON media_programs(type)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Seerah Events - Add search indexes
        if (Schema::hasTable('seerah_events')) {
            try {
                DB::statement('CREATE INDEX idx_seerah_events_type ON seerah_events(event_type)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_seerah_events_importance ON seerah_events(importance_level)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_seerah_events_hijri_date ON seerah_events(hijri_date)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Sahaba - Add search indexes
        if (Schema::hasTable('sahabis')) {
            try {
                DB::statement('CREATE INDEX idx_sahabis_status ON sahabis(status)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_sahabis_tribe ON sahabis(tribe)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
        }

        // Quran Verses - Add search indexes
        if (Schema::hasTable('quran_verses')) {
            try {
                DB::statement('CREATE INDEX idx_quran_verses_surah ON quran_verses(surah_id)');
            } catch (\Exception $e) {
                // Index might already exist, skip
            }
            try {
                DB::statement('CREATE INDEX idx_quran_verses_juz ON quran_verses(juz_number)');
            } catch (\Exception $e) {
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
            Schema::table('articles', function (Blueprint $table) {
                $table->dropIndex('idx_articles_status');
                $table->dropIndex('idx_articles_published_at');
            });
        }

        if (Schema::hasTable('hadith')) {
            Schema::table('hadith', function (Blueprint $table) {
                $table->dropIndex('idx_hadith_book_id');
                $table->dropIndex('idx_hadith_chapter_id');
                $table->dropIndex('idx_hadith_authenticity');
            });
        }

        if (Schema::hasTable('media_programs')) {
            Schema::table('media_programs', function (Blueprint $table) {
                $table->dropIndex('idx_media_programs_category');
                $table->dropIndex('idx_media_programs_type');
            });
        }

        if (Schema::hasTable('seerah_events')) {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->dropIndex('idx_seerah_events_type');
                $table->dropIndex('idx_seerah_events_importance');
                $table->dropIndex('idx_seerah_events_hijri_date');
            });
        }

        if (Schema::hasTable('sahabis')) {
            Schema::table('sahabis', function (Blueprint $table) {
                $table->dropIndex('idx_sahabis_status');
                $table->dropIndex('idx_sahabis_tribe');
            });
        }

        if (Schema::hasTable('quran_verses')) {
            Schema::table('quran_verses', function (Blueprint $table) {
                $table->dropIndex('idx_quran_verses_surah');
                $table->dropIndex('idx_quran_verses_juz');
            });
        }
    }
};
