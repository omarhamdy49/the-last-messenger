<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — per-resource verse content (translations, tafsirs) and
 * per-resource chapter introductions. `qf_id` is the QF row id used as the sync
 * record key; manual rows leave it null. `is_locked` rows survive a sync replace.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_translations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('verse_id');
            $table->unsignedBigInteger('qf_id')->nullable();
            // LONGTEXT, not TEXT: some translations carry inline commentary past the
            // 65,535-byte TEXT limit (see 2026_09_18_000011).
            $table->longText('text');
            $table->json('foot_notes')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('verse_id')->references('id')->on('quran_verses')->cascadeOnDelete();
            $table->unique(['resource_id', 'verse_id']);
            $table->index('verse_id');
        });

        Schema::create('quran_tafsirs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('verse_id');
            $table->unsignedBigInteger('qf_id')->nullable();
            $table->longText('text');
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('verse_id')->references('id')->on('quran_verses')->cascadeOnDelete();
            $table->unique(['resource_id', 'verse_id']);
            $table->index('verse_id');
        });

        Schema::create('quran_chapter_infos', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('resource_id')->constrained('quran_resources')->cascadeOnDelete();
            $table->unsignedSmallInteger('chapter_id');
            $table->unsignedBigInteger('qf_id')->nullable();
            $table->string('language_iso', 10)->nullable();
            $table->longText('text');
            $table->text('short_text')->nullable();
            $table->string('source')->nullable();
            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('quran_chapters')->cascadeOnDelete();
            $table->unique(['resource_id', 'chapter_id']);
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_translations', function (Blueprint $table): void {
                $table->fullText('text', 'qt_text_ft');
            });
            Schema::table('quran_tafsirs', function (Blueprint $table): void {
                $table->fullText('text', 'qtf_text_ft');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_chapter_infos');
        Schema::dropIfExists('quran_tafsirs');
        Schema::dropIfExists('quran_translations');
    }
};
