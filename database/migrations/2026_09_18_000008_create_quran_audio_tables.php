<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — audio.
 *
 * Two QF id spaces that must never be mixed:
 *  - ayah-by-ayah recitations  → quran_audio_files (one file per verse)
 *  - chapter reciters          → quran_chapter_audio_files (one file per chapter)
 *    with per-verse timestamps (+ word segments) in quran_chapter_audio_timestamps.
 *
 * URLs are stored absolute and streamed from source unless `is_local` (downloaded
 * or uploaded to the public disk).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_audio_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('verse_id');
            $table->unsignedBigInteger('qf_id')->nullable();
            $table->string('url', 500);
            $table->string('format', 8)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('segments')->nullable();
            $table->boolean('is_local')->default(false);
            $table->timestamps();

            $table->foreign('verse_id')->references('id')->on('quran_verses')->cascadeOnDelete();
            $table->unique(['resource_id', 'verse_id']);
        });

        Schema::create('quran_chapter_audio_files', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('chapter_id');
            $table->unsignedBigInteger('qf_id')->nullable();
            $table->string('url', 500);
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('format', 8)->default('mp3');
            $table->unsignedInteger('duration_ms')->nullable();
            $table->boolean('is_local')->default(false);
            $table->boolean('has_timestamps')->default(false);
            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('quran_chapters')->cascadeOnDelete();
            $table->unique(['resource_id', 'chapter_id']);
        });

        Schema::create('quran_chapter_audio_timestamps', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('chapter_audio_file_id')->constrained('quran_chapter_audio_files')->cascadeOnDelete();
            $table->unsignedSmallInteger('verse_id');
            $table->unsignedInteger('timestamp_from');
            $table->unsignedInteger('timestamp_to');
            // Mirrors QF, whose legacy data can be negative.
            $table->integer('duration')->nullable();
            $table->json('segments')->nullable();
            $table->timestamps();

            $table->foreign('verse_id')->references('id')->on('quran_verses')->cascadeOnDelete();
            $table->unique(['chapter_audio_file_id', 'verse_id'], 'qcat_file_verse_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_chapter_audio_timestamps');
        Schema::dropIfExists('quran_chapter_audio_files');
        Schema::dropIfExists('quran_audio_files');
    }
};
