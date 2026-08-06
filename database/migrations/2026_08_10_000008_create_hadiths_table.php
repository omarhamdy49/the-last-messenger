<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §8 — the central table.
 *
 * `collection_id` is denormalised even though it is reachable through
 * `book_id`: collection-scoped browse and the (collection, reference_key)
 * lookup are the hottest paths, and bookless collections need a direct parent.
 * The composite foreign keys below are what make that denormalisation safe —
 * without them a hadith could name collection A, a book in collection B and a
 * chapter in a third book entirely.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadiths', function (Blueprint $table): void {
            $table->id();

            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();
            // NULLABLE: bookless collections are real, and there are no
            // synthetic books any more.
            $table->unsignedBigInteger('book_id')->nullable();
            $table->unsignedBigInteger('chapter_id')->nullable();

            // The public identity. Derived from source structural position, not
            // from the displayed number — assuming uniqueness on the displayed
            // number cost this project 56 Tirmidhi records once already.
            $table->string('reference_key', 100);

            // The displayed scholarly number. Duplicates ARE allowed.
            $table->string('number', 50);
            $table->string('in_book_number', 50)->nullable();
            $table->unsignedInteger('sort_order');

            $table->string('grade', 10)->nullable();
            $table->text('raw_grade')->nullable();

            // Nullable: 38 records legitimately have no Arabic text
            // (config/islamic-data.php). The CHECK below stops them being
            // published rather than dropping or fabricating them.
            $table->longText('text_ar')->nullable();
            $table->longText('text_en')->nullable();
            $table->longText('search_ar')->nullable();
            $table->longText('search_en')->nullable();

            // TEXT, not string: observed up to 2,806 characters.
            $table->text('narrated_by')->nullable();

            $table->string('canonical_reference', 255)->nullable();
            $table->string('in_book_reference', 255)->nullable();
            $table->string('translation_reference', 255)->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'reference_key'], 'uq_hadiths_reference');
            $table->unique(['collection_id', 'sort_order'], 'uq_hadiths_order');

            // Scholarly-reference lookup. NON-unique — numbers repeat.
            $table->index(['collection_id', 'number']);
            $table->index(['collection_id', 'is_published', 'sort_order']);
            $table->index(['book_id', 'is_published', 'sort_order']);
            $table->index(['chapter_id', 'is_published', 'sort_order']);
            $table->index(['is_published', 'grade', 'id']);
            // Kept ALONGSIDE the composite above: this is what random selection
            // uses. Do not drop it without an EXPLAIN proving otherwise.
            $table->index(['is_published', 'id']);

            // Child indexes for the composite FKs, declared explicitly rather
            // than relying on InnoDB to invent them.
            $table->index(['book_id', 'collection_id'], 'ix_hadiths_book_collection');
            $table->index(['chapter_id', 'book_id'], 'ix_hadiths_chapter_book');
        });

        Schema::table('hadiths', function (Blueprint $table): void {
            $table->foreign(['book_id', 'collection_id'], 'fk_hadiths_book_collection')
                ->references(['id', 'collection_id'])->on('hadith_books')->restrictOnDelete();

            $table->foreign(['chapter_id', 'book_id'], 'fk_hadiths_chapter_book')
                ->references(['id', 'book_id'])->on('hadith_chapters')->restrictOnDelete();
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            // A NULL component skips the composite FK check, which is correct
            // for genuinely bookless rows — so this CHECK closes the one case
            // the FK cannot see: a chapter with no book.
            DB::statement(
                'ALTER TABLE hadiths ADD CONSTRAINT chk_hadiths_chapter_requires_book '.
                'CHECK (chapter_id IS NULL OR book_id IS NOT NULL)'
            );

            // Publication requires Arabic. The 38 Arabic-less records exist,
            // they are simply not publishable.
            DB::statement(
                'ALTER TABLE hadiths ADD CONSTRAINT chk_hadiths_publishable '.
                'CHECK (is_published = 0 OR (text_ar IS NOT NULL AND search_ar IS NOT NULL))'
            );

            // TWO separate single-column FULLTEXT indexes, never one composite:
            // MySQL requires a MATCH column list to exactly equal an index, and
            // search issues separate Arabic and English branches merged by
            // score. Needs innodb_ft_min_token_size = 2 for Arabic.
            DB::statement('ALTER TABLE hadiths ADD FULLTEXT ft_hadiths_search_ar (search_ar)');
            DB::statement('ALTER TABLE hadiths ADD FULLTEXT ft_hadiths_search_en (search_en)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hadiths');
    }
};
