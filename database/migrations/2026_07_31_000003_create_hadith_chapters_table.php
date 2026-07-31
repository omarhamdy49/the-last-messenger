<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §3.
 *
 * Deletion is RESTRICT, not nullOnDelete: orphaning every hadith in a chapter
 * because an editor deleted the chapter is not an acceptable outcome (FR-010).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hadith_chapters')) {
            return;
        }

        Schema::create('hadith_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();
            $table->string('chapter_number', 20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('title');
            // Preamble before the chapter's hadiths, and its closing passage.
            $table->json('intro')->nullable();
            $table->json('ending')->nullable();
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'chapter_number']);
            $table->index(['book_id', 'sort_order']);
        });

        // Parent key for the composite foreign key (chapter_id, book_id).
        Schema::table('hadith_chapters', function (Blueprint $table): void {
            $table->unique(['id', 'book_id'], 'hadith_chapters_id_book_id_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('hadith_chapters', function (Blueprint $table): void {
                $table->dropUnique('hadith_chapters_id_book_id_unique');
            });
        }

        Schema::dropIfExists('hadith_chapters');
    }
};
