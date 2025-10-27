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
        Schema::create('quran_tafseer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->onDelete('cascade');
            $table->string('tafseer_name'); // e.g., "Ibn Kathir", "Al-Jalalayn", "Saadi"
            $table->string('language', 2); // ar, en, fr, tr, ur
            $table->text('text'); // The tafseer text
            $table->text('footnotes')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('verse_id');
            $table->index('tafseer_name');
            $table->index('language');
            $table->index(['verse_id', 'tafseer_name', 'language']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_tafseer');
    }
};

