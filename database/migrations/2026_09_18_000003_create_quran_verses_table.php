<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * 006-quran-qf-refactor — verses keyed on the QF verse id (1–6236, mushaf order).
 *
 * One nullable column per script: the reader needs exactly one per request, so a
 * per-script child table would only add a join (plan.md, Complexity Tracking).
 * `is_locked` marks an editorial override that the QF import must not overwrite.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_verses', function (Blueprint $table): void {
            $table->unsignedSmallInteger('id')->primary();
            $table->unsignedSmallInteger('chapter_id');
            $table->unsignedSmallInteger('verse_number');
            $table->string('verse_key', 8)->unique();
            $table->unsignedSmallInteger('verse_index');
            $table->unsignedSmallInteger('juz_number');
            $table->unsignedSmallInteger('hizb_number');
            $table->unsignedSmallInteger('rub_el_hizb_number');
            $table->unsignedSmallInteger('ruku_number');
            $table->unsignedSmallInteger('manzil_number');
            $table->unsignedSmallInteger('page_number');
            $table->string('sajdah_type', 12)->nullable();
            $table->unsignedTinyInteger('sajdah_number')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_path')->nullable();
            $table->unsignedSmallInteger('image_width')->nullable();

            $table->text('text_uthmani')->nullable();
            $table->text('text_uthmani_simple')->nullable();
            $table->text('text_uthmani_tajweed')->nullable();
            $table->text('text_imlaei')->nullable();
            $table->text('text_imlaei_simple')->nullable();
            $table->text('text_indopak')->nullable();
            $table->text('text_indopak_nastaleeq')->nullable();
            $table->text('text_qpc_hafs')->nullable();
            $table->text('text_qpc_nastaleeq')->nullable();
            $table->text('code_v1')->nullable();
            $table->text('code_v2')->nullable();
            $table->unsignedSmallInteger('v1_page')->nullable();
            $table->unsignedSmallInteger('v2_page')->nullable();

            $table->boolean('is_locked')->default(false);
            $table->timestamps();

            $table->foreign('chapter_id')->references('id')->on('quran_chapters')->restrictOnDelete();
            $table->unique(['chapter_id', 'verse_number']);
            $table->index('juz_number');
            $table->index('hizb_number');
            $table->index('rub_el_hizb_number');
            $table->index('ruku_number');
            $table->index('manzil_number');
            $table->index('page_number');
            $table->index('sajdah_number');
        });

        if (DB::getDriverName() === 'mysql') {
            Schema::table('quran_verses', function (Blueprint $table): void {
                $table->fullText(['text_uthmani_simple', 'text_imlaei_simple'], 'qv_text_ft');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_verses');
    }
};
