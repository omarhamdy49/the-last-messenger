<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §3 declared `UNIQUE (book_id, chapter_number)`, assuming a
 * chapter number identifies a chapter within its book. The real corpus
 * disproves it: across Sahih al-Bukhari, **354** chapters repeat a number
 * already used in the same book, and they are genuinely different chapters —
 * book 10 labels both "A woman shall ask her husband's permission" and "Women
 * praying behind men" as chapter 166.
 *
 * Under the old key those pairs collapsed into one row on import: 3,979 source
 * chapters became 3,629. Identity moves to `sort_order`, which is document
 * order on the source page — genuinely unique within a book and stable across
 * re-crawls of the same page.
 *
 * `chapter_number` stays as the source's own label, now free to repeat, because
 * it is what a reader sees cited.
 */
return new class extends Migration
{
    /**
     * Written defensively because the two databases had drifted: the dev schema
     * never carried `hadith_chapters_book_id_chapter_number_unique` while the
     * test schema did. A migration that assumes one shape fails on the other and
     * leaves the pair further apart, which is how the drift compounds.
     */
    public function up(): void
    {
        Schema::table('hadith_chapters', function (Blueprint $table): void {
            if ($this->indexExists('hadith_chapters_book_id_chapter_number_unique')) {
                $table->dropUnique('hadith_chapters_book_id_chapter_number_unique');
            }

            if (! $this->indexExists('hadith_chapters_book_id_sort_order_unique')) {
                $table->unique(['book_id', 'sort_order'], 'hadith_chapters_book_id_sort_order_unique');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hadith_chapters', function (Blueprint $table): void {
            if ($this->indexExists('hadith_chapters_book_id_sort_order_unique')) {
                $table->dropUnique('hadith_chapters_book_id_sort_order_unique');
            }
        });
    }

    private function indexExists(string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'hadith_chapters')
            ->where('INDEX_NAME', $name)
            ->exists();
    }
};
