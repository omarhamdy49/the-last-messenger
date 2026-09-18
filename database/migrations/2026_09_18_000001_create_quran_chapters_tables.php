<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — chapters (surahs) keyed on their number, 1–114.
 *
 * Imported from the Quran Foundation Content API (`/chapters`). The id IS the
 * chapter number, so it is not auto-incrementing. Localized names live in
 * quran_chapter_translations (HasLocalizedContent), not JSON columns.
 *
 * `revelation_location_id` is created here rather than by
 * 2026_08_18_000003_add_location_foreign_keys: `locations` already exists at this
 * date, and the chapters table does not exist at that one.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_chapters', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('revelation_place', 10);
            $table->unsignedSmallInteger('revelation_order');
            $table->boolean('bismillah_pre')->default(true);
            $table->string('name_simple', 100);
            $table->string('name_complex', 100);
            $table->string('name_arabic', 100);
            $table->unsignedSmallInteger('verses_count');
            $table->unsignedSmallInteger('first_page');
            $table->unsignedSmallInteger('last_page');
            $table->foreignId('revelation_location_id')->nullable()
                ->constrained('locations')->nullOnDelete();
            $table->string('cover_image')->nullable();
            $table->boolean('is_published')->default(true);
            $table->timestamps();

            $table->index('revelation_place');
            $table->index('revelation_order');
        });

        Schema::create('quran_chapter_translations', function (Blueprint $table): void {
            $table->id();
            $table->unsignedSmallInteger('chapter_id');
            $table->string('locale', 5);
            $table->string('name', 150);
            $table->boolean('is_ready')->default(false);
            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('quran_chapters')->cascadeOnDelete();
            $table->unique(['chapter_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_chapter_translations');
        Schema::dropIfExists('quran_chapters');
    }
};
