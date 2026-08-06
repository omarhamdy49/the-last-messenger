<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create translation tables for Phase 4 models.
 *
 * The person_* and hadith_* tables that this migration originally created were
 * removed with their modules: `people` and the hadith tables no longer exist,
 * so their foreign keys cannot resolve on a fresh build. The surviving tables
 * below are unaffected.
 *
 * Remaining:
 * - reciter_translations
 * - seerah_participant_translations
 * - language_translations
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('reciter_translations')) {
            Schema::create('reciter_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('reciter_id')->constrained('quran_reciters')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->longText('description')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();
                $table->unique(['reciter_id', 'locale']);
                $table->index('locale');
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE reciter_translations ADD FULLTEXT INDEX ft_reciter_translations (name, description)');
            }
        }

        if (! Schema::hasTable('seerah_participant_translations')) {
            Schema::create('seerah_participant_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('seerah_participant_id')->constrained('seerah_participants')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->longText('description')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();
                // Explicit short name — the auto-generated one is 68 chars, over MySQL's 64 limit.
                $table->unique(['seerah_participant_id', 'locale'], 'spt_participant_locale_unique');
                $table->index('locale');
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE seerah_participant_translations ADD FULLTEXT INDEX ft_seerah_participant_translations (name, description)');
            }
        }

        if (! Schema::hasTable('language_translations')) {
            Schema::create('language_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('language_id')->constrained('languages')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();
                $table->unique(['language_id', 'locale']);
                $table->index('locale');
            });
        }

        // hadith_source_translations / hadith_theme_translations / hadith_chapter_translations were
        // removed by the 2026-07-30 Hadith rebuild: the new schema stores translations in spatie
        // translatable JSON columns on hadith_collections/books/chapters/themes, so these side
        // tables are redundant. See specs/001-hadith-rebuild/data-model.md.
    }

    public function down(): void
    {
        Schema::dropIfExists('language_translations');
        Schema::dropIfExists('seerah_participant_translations');
        Schema::dropIfExists('reciter_translations');
    }
};
