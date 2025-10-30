<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->id();
            $table->integer('number')->unique(); // 1-114
            $table->json('name'); // {"ar": "الفاتحة", "en": "Al-Fatihah", "transliteration": "Al-Faatiha"}
            $table->json('meaning')->nullable(); // Meaning of the surah name

            // Revelation information
            $table->enum('revelation_place', ['makkah', 'madinah']);
            $table->integer('revelation_order')->nullable(); // Order of revelation (1-114)

            // Structure
            $table->integer('verses_count'); // Number of verses (ayahs)
            $table->integer('words_count')->nullable();
            $table->integer('letters_count')->nullable();

            // Juz information
            $table->json('juz')->nullable(); // ["1", "2"] if spans multiple juz

            // Description
            $table->json('description')->nullable(); // Multi-language description
            $table->json('theme')->nullable(); // Main themes

            // Audio
            $table->json('audio_urls')->nullable(); // Multiple reciters

            // Metadata
            $table->integer('page_number')->nullable(); // Starting page in Mushaf
            $table->timestamps();

            // Indexes
            $table->index('number');
            $table->index('revelation_place');
            $table->index('revelation_order');
            $table->index('verses_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_surahs');
    }
};
