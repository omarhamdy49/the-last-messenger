<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — the five structural axes (juz, hizb, rubʿ al-ḥizb,
 * manzil, rukūʿ) in one table; QF returns an identical shape for each. Pages are
 * mushaf-specific and live in quran_mushaf_pages.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_sections', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 12);
            $table->unsignedSmallInteger('number');
            $table->unsignedSmallInteger('surah_ruku_number')->nullable();
            $table->unsignedSmallInteger('first_verse_id');
            $table->unsignedSmallInteger('last_verse_id');
            $table->unsignedSmallInteger('verses_count');
            $table->json('verse_mapping');
            $table->timestamps();

            $table->unique(['type', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_sections');
    }
};
