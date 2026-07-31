<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §4 — identity and structure only, no text.
 *
 * `collection_id` is denormalised even though it is reachable via `book_id`:
 * the canonical lookup (collection, hadith_number) and every collection-scoped
 * list would otherwise join through books, and that is the hottest query path.
 * The composite foreign keys below are what make the denormalisation safe.
 *
 * No `text_hash` column: duplicate detection is a one-time report, so the hash
 * is computed in that report's query rather than stored and indexed across 51k
 * rows. No `views_count` either — nothing in the spec asks for one.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hadith_hadiths')) {
            return;
        }

        Schema::create('hadith_hadiths', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();
            // NOT NULL: bookless collections get a hidden synthetic book.
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();
            // NULL where the collection has has_chapters = 0.
            $table->foreignId('chapter_id')->nullable()->constrained('hadith_chapters')->restrictOnDelete();
            // Canonical, e.g. '7533' or '1a'.
            $table->string('hadith_number', 50);
            $table->unsignedInteger('in_book_number')->nullable();
            $table->string('volume_number', 20)->nullable();
            // External keys. ~65% / ~58% populated, so never identity columns.
            $table->unsignedInteger('sunnah_urn')->nullable()->unique();
            $table->unsignedInteger('sunnah_urn_en')->nullable()->unique();
            // Derived from hadith_gradings; never edited directly.
            $table->enum('display_grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])
                ->default('unknown');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            // The canonical identity.
            $table->unique(['collection_id', 'hadith_number']);
            // Public reading order, book and chapter scoped.
            $table->index(['book_id', 'is_published', 'in_book_number']);
            $table->index(['chapter_id', 'is_published', 'in_book_number']);
            // Random selection and pagination.
            $table->index(['is_published', 'id']);
        });

        // The integrity requirement (FR-009). Without these, a hadith can name
        // collection A, a book belonging to collection B, and a chapter
        // belonging to a third book entirely.
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->foreign(['book_id', 'collection_id'], 'hadith_hadiths_book_collection_foreign')
                ->references(['id', 'collection_id'])->on('hadith_books')
                ->restrictOnDelete();

            $table->foreign(['chapter_id', 'book_id'], 'hadith_hadiths_chapter_book_foreign')
                ->references(['id', 'book_id'])->on('hadith_chapters')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_hadiths');
    }
};
