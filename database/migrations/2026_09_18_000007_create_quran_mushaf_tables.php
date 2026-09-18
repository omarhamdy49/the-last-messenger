<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — mushaf layouts from QF `mushafs` sync snapshots:
 * the mushaf, its page → verse-range map, and every word's line/position on a page.
 * Font files are not part of snapshots; `quran:qf:download-assets` fetches them.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_mushafs', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->string('name', 100);
            $table->string('description')->nullable();
            $table->unsignedSmallInteger('pages_count')->default(604);
            $table->unsignedTinyInteger('lines_per_page')->default(15);
            $table->string('default_font_name', 20)->nullable();
            $table->string('mapping_mode', 20)->nullable();
            $table->string('qirat_name', 50)->nullable();
            $table->string('font_version', 10)->nullable();
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(false);
            $table->timestamp('fonts_downloaded_at')->nullable();
            $table->unsignedInteger('records_count')->default(0);
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_mushaf_pages', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedSmallInteger('mushaf_id');
            $table->unsignedSmallInteger('page_number');
            $table->unsignedSmallInteger('first_verse_id');
            $table->unsignedSmallInteger('last_verse_id');
            $table->unsignedInteger('first_word_id')->nullable();
            $table->unsignedInteger('last_word_id')->nullable();
            $table->unsignedSmallInteger('verses_count');
            $table->json('verse_mapping');
            $table->timestamps();

            $table->foreign('mushaf_id')->references('id')->on('quran_mushafs')->cascadeOnDelete();
            $table->unique(['mushaf_id', 'page_number']);
        });

        Schema::create('quran_mushaf_words', function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->unsignedSmallInteger('mushaf_id');
            $table->unsignedInteger('word_id');
            $table->unsignedSmallInteger('verse_id');
            $table->string('text', 64)->nullable();
            $table->string('char_type', 12)->default('word');
            $table->unsignedSmallInteger('page_number');
            $table->unsignedTinyInteger('line_number');
            $table->unsignedSmallInteger('position_in_verse');
            $table->unsignedSmallInteger('position_in_line');
            $table->unsignedSmallInteger('position_in_page');
            $table->string('css_class', 64)->nullable();
            $table->string('css_style')->nullable();
            $table->timestamps();

            $table->foreign('mushaf_id')->references('id')->on('quran_mushafs')->cascadeOnDelete();
            $table->foreign('word_id')->references('id')->on('quran_words')->cascadeOnDelete();
            $table->unique(['mushaf_id', 'word_id']);
            $table->index(['mushaf_id', 'page_number', 'line_number', 'position_in_line'], 'mw_render_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_mushaf_words');
        Schema::dropIfExists('quran_mushaf_pages');
        Schema::dropIfExists('quran_mushafs');
    }
};
