<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Create translation tables for Phase 4 models:
 * - person_translations
 * - reciter_translations
 * - seerah_participant_translations
 * - language_translations
 * - hadith_source_translations
 * - hadith_theme_translations
 * - hadith_chapter_translations
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('person_translations')) {
            Schema::create('person_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('person_id')->constrained('people')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->string('title')->nullable();
                $table->longText('biography')->nullable();
                $table->string('birth_place')->nullable();
                $table->string('death_place')->nullable();
                $table->string('burial_place')->nullable();
                $table->string('occupation')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();
                $table->unique(['person_id', 'locale']);
                $table->index('locale');
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE person_translations ADD FULLTEXT INDEX ft_person_translations (name, biography)');
            }
        }

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

        if (! Schema::hasTable('hadith_source_translations')) {
            Schema::create('hadith_source_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hadith_source_id')->constrained('hadith_sources')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->longText('description')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->index('locale');
                $table->unique(['hadith_source_id', 'locale']);
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE hadith_source_translations ADD FULLTEXT INDEX ft_hadith_source_translations (name, description)');
            }
        }

        if (! Schema::hasTable('hadith_theme_translations')) {
            Schema::create('hadith_theme_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->longText('description')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->index('locale');
                $table->unique(['hadith_theme_id', 'locale']);
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE hadith_theme_translations ADD FULLTEXT INDEX ft_hadith_theme_translations (name, description)');
            }
        }

        if (! Schema::hasTable('hadith_chapter_translations')) {
            Schema::create('hadith_chapter_translations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('hadith_chapter_id')->constrained('hadith_chapters')->cascadeOnDelete();
                $table->string('locale', 5);
                $table->string('name')->nullable();
                $table->longText('description')->nullable();
                $table->boolean('is_ready')->default(false);
                $table->timestamps();
                $table->index('locale');
                $table->unique(['hadith_chapter_id', 'locale']);
            });

            if (DB::getDriverName() === 'mysql') {
                DB::statement('ALTER TABLE hadith_chapter_translations ADD FULLTEXT INDEX ft_hadith_chapter_translations (name, description)');
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_chapter_translations');
        Schema::dropIfExists('hadith_theme_translations');
        Schema::dropIfExists('hadith_source_translations');
        Schema::dropIfExists('language_translations');
        Schema::dropIfExists('seerah_participant_translations');
        Schema::dropIfExists('reciter_translations');
        Schema::dropIfExists('person_translations');
    }
};
