<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — playlist items re-keyed on the new catalogue: a chapter
 * plus the chapter reciter (a quran_resources row of kind chapter_reciter) chosen
 * for it. Replaces 2026_09_14_000002, which pointed at quran_surahs/quran_reciters.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_playlist_items', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('playlist_id')->constrained('quran_playlists')->cascadeOnDelete();
            $table->unsignedSmallInteger('chapter_id');
            $table->foreignId('resource_id')->nullable()->constrained('quran_resources')->nullOnDelete();
            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('quran_chapters')->cascadeOnDelete();
            $table->unique(['playlist_id', 'chapter_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_playlist_items');
    }
};
