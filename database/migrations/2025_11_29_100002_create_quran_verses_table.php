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
        Schema::create('quran_verses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('surah_id')->constrained('quran_surahs')->cascadeOnDelete();
            $table->unsignedInteger('verse_number');
            $table->string('verse_key', 20); // Format: "2:255" or "1:1"
            $table->unsignedInteger('juz_number');
            $table->unsignedInteger('hizb_number');
            $table->unsignedInteger('rub_el_hizb_number');
            $table->unsignedInteger('ruku_number');
            $table->unsignedInteger('manzil_number')->nullable();
            $table->unsignedInteger('sajdah_number')->nullable();
            $table->unsignedInteger('page_number');

            $table->timestamps();

            // Indexes
            $table->index('surah_id');
            $table->index('verse_number');
            $table->index('verse_key');
            $table->index('juz_number');
            $table->index('hizb_number');
            $table->index('page_number');
            $table->index('sajdah_number');
            $table->unique(['surah_id', 'verse_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_verses');
    }
};
