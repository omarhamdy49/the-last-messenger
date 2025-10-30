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
        Schema::create('quran_verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('quran_surahs')->onDelete('cascade');
            $table->integer('verse_number'); // Verse number within surah
            $table->integer('verse_key')->unique(); // Global verse number (1-6236)

            // Arabic text
            $table->text('text_uthmani'); // Uthmani script (with tajweed marks)
            $table->text('text_simple'); // Simple Arabic text (no marks)
            $table->text('text_imlaei')->nullable(); // Imlaei script

            // Translations
            $table->json('translations'); // {"en": "...", "fr": "...", "tr": "...", "ur": "..."}

            // Transliteration
            $table->text('transliteration')->nullable();

            // Juz, Hizb, Page
            $table->integer('juz_number');
            $table->integer('hizb_number')->nullable();
            $table->integer('page_number'); // Page in Mushaf
            $table->integer('rub_number')->nullable(); // Rub al-Hizb

            // Sajda (prostration) ayah
            $table->boolean('is_sajda')->default(false);
            $table->enum('sajda_type', ['recommended', 'obligatory'])->nullable();

            // Audio
            $table->json('audio_urls')->nullable(); // {"reciter_1": "url", "reciter_2": "url"}

            // Word count
            $table->integer('words_count')->nullable();
            $table->integer('letters_count')->nullable();

            $table->timestamps();

            // Indexes
            $table->index('surah_id');
            $table->index('verse_number');
            $table->index('verse_key');
            $table->index(['surah_id', 'verse_number']);
            $table->index('juz_number');
            $table->index('page_number');
            $table->index('is_sajda');

            // Unique constraint
            $table->unique(['surah_id', 'verse_number']);
        });

        // Add full-text index for Arabic text search (MySQL only)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE quran_verses ADD FULLTEXT fulltext_arabic (text_simple, text_uthmani)');

            // Add generated column for English translation search
            DB::statement("ALTER TABLE quran_verses ADD COLUMN translation_en_searchable TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED");
            DB::statement('ALTER TABLE quran_verses ADD FULLTEXT fulltext_translation_en (translation_en_searchable)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_verses');
    }
};
