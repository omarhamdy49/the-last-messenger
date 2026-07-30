<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Identity and structure only. All text lives in hadith_texts, one row per locale.
     */
    public function up(): void
    {
        Schema::create('hadith_hadiths', function (Blueprint $table): void {
            $table->id();

            // Denormalised on purpose: collection-scoped lists and the canonical
            // (collection, hadith_number) lookup must not join through hadith_books.
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();

            // Null for collections with has_chapters = false.
            $table->foreignId('chapter_id')->nullable()->constrained('hadith_chapters')->nullOnDelete();

            // The canonical reference number, e.g. "7533" or "1a".
            $table->string('hadith_number', 50);

            // sunnah.com's ourHadithNumber — drives the "In-book reference" line.
            $table->unsignedInteger('in_book_number')->nullable();
            $table->string('volume_number', 20)->nullable();

            // Only ~65% of source records carry a URN, so this can never be an identity column.
            // MySQL permits many NULLs under a UNIQUE index, which still catches import collisions.
            $table->unsignedInteger('sunnah_urn')->nullable()->unique();
            $table->unsignedInteger('sunnah_urn_en')->nullable()->unique();

            // Denormalised rollup of hadith_gradings for badges and filters.
            // Written only by the importer and HadithAdminService — see the invariant in
            // TODO_hadith-refactor.md §2.4.
            $table->enum('grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])->default('unknown');

            $table->char('text_hash', 64);

            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'hadith_number']);
            $table->index(['book_id', 'in_book_number'], 'idx_hadith_book_reading_order');
            $table->index(['chapter_id', 'in_book_number'], 'idx_hadith_chapter_reading_order');
            $table->index(['collection_id', 'grade'], 'idx_hadith_collection_grade');
            $table->index('text_hash');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_hadiths');
    }
};
