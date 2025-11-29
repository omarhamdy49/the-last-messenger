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
            $table->string('revelation_place', 50); // makkah, madinah
            $table->unsignedInteger('revelation_order'); // 1-114
            $table->boolean('bismillah_pre')->default(true);
            $table->string('name_ar', 100); // Arabic name
            $table->string('name_en', 100); // English name
            $table->string('transliteration', 100)->nullable();
            $table->unsignedInteger('verses_count');
            $table->json('pages')->nullable(); // Array of page numbers

            $table->timestamps();

            // Indexes
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
