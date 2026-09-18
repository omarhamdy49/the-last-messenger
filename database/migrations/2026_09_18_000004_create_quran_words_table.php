<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — words keyed on the QF word id. Positions here are for the
 * default mushaf; other mushafs' layouts live in quran_mushaf_words. Per-language
 * word glosses are in quran_word_glosses (created after quran_resources).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_words', function (Blueprint $table): void {
            $table->unsignedInteger('id')->primary();
            $table->unsignedSmallInteger('verse_id');
            $table->unsignedSmallInteger('position');
            $table->string('location', 16)->unique();
            $table->string('char_type', 12)->default('word');
            $table->text('text_uthmani')->nullable();
            $table->text('text_uthmani_simple')->nullable();
            $table->text('text_indopak')->nullable();
            $table->text('text_imlaei')->nullable();
            $table->text('text_qpc_hafs')->nullable();
            $table->string('code_v1', 32)->nullable();
            $table->string('code_v2', 32)->nullable();
            $table->unsignedSmallInteger('v1_page')->nullable();
            $table->unsignedSmallInteger('v2_page')->nullable();
            $table->unsignedSmallInteger('page_number');
            $table->unsignedTinyInteger('line_number');
            $table->string('audio_url')->nullable();
            $table->timestamps();

            $table->foreign('verse_id')->references('id')->on('quran_verses')->cascadeOnDelete();
            $table->index(['verse_id', 'position']);
            $table->index(['page_number', 'line_number', 'position']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_words');
    }
};
