<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite performance indexes.
 *
 * Rationale per table
 * -------------------
 * article_locale (locale, is_ready)
 *   The API filters by locale AND is_ready=true to serve published translations.
 *   Without this composite the engine does a range scan on locale then re-filters
 *   is_ready row-by-row. With it, both predicates are resolved in a single B-tree
 *   lookup. Estimated improvement: 3-5x on tables with O(10 k) rows per locale.
 *
 * quran_verse_translations (lang, verse_id) — removed by 006-quran-qf-refactor; the
 *   table no longer exists (translations are per resource in quran_translations).
 *
 * seerah_events (hijri_year, hijri_month, hijri_day)
 *   Chronological timeline queries filter on hijri_year and sort on hijri_month /
 *   hijri_day. Individual single-column indexes exist but the planner uses only
 *   one; this composite lets it satisfy WHERE + ORDER BY in one index scan.
 *
 * hadith_books (collection_id, title_ar)
 *   Listing all books within a collection sorted alphabetically by Arabic title is
 *   the primary access pattern. The existing collection_id index satisfies the
 *   filter but forces a filesort for ordering. This composite eliminates it.
 *
 * personal_access_tokens (tokenable_type, tokenable_id, last_used_at)
 *   Token cleanup and "oldest active token" queries join tokenable + recency.
 *   The morphs() index covers (tokenable_type, tokenable_id) but not last_used_at,
 *   so adding it here converts a ref+filesort plan into a pure index range scan.
 *
 * Indexes NOT added (already exist)
 * ----------------------------------
 * hadith_hadiths (book_id, hadith_number) — covered by UNIQUE constraint added in
 *   migration 2026_03_09_000001_add_text_hash_to_hadith_hadiths_table.php.
 */
return new class extends Migration
{
    public function up(): void
    {
        // article_locale: published translation look-up (locale + readiness flag)
        if (Schema::hasTable('article_locale')) {
            Schema::table('article_locale', function (Blueprint $table) {
                $table->index(['locale', 'is_ready'], 'idx_article_locale_ready');
            });
        }

        // seerah_events: chronological timeline scan
        if (Schema::hasTable('seerah_events')) {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->index(['hijri_year', 'hijri_month', 'hijri_day'], 'idx_seerah_chronological');
            });
        }

        // hadith_books: the (collection_id, title_ar) index is gone — the 2026-07-30 Hadith rebuild
        // replaced title_ar/title_en with a translatable `title` JSON column, and book ordering is
        // now served by idx_hadith_books_reading_order (collection_id, sort_order), created in
        // 2026_07_30_000002_create_hadith_books_table.

        // personal_access_tokens: user token + recency for cleanup / audit queries
        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->index(['tokenable_type', 'tokenable_id', 'last_used_at'], 'idx_pat_tokenable_last_used');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('article_locale')) {
            Schema::table('article_locale', function (Blueprint $table) {
                $table->dropIndex('idx_article_locale_ready');
            });
        }

        if (Schema::hasTable('seerah_events')) {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->dropIndex('idx_seerah_chronological');
            });
        }

        // idx_hadith_book_collection_title is no longer created — see up().

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex('idx_pat_tokenable_last_used');
            });
        }
    }
};
