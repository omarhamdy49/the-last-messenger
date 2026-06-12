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
 * quran_verse_translations (lang, verse_id)
 *   Supports "get all Arabic translations in verse_id order" queries used by the
 *   Quran listing endpoint. The existing single-column lang index cannot satisfy
 *   ORDER BY verse_id without a filesort. Covering composite eliminates that sort.
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

        // quran_verse_translations: language-scoped verse listing with ordering
        if (Schema::hasTable('quran_verse_translations')) {
            Schema::table('quran_verse_translations', function (Blueprint $table) {
                $table->index(['lang', 'verse_id'], 'idx_qvt_lang_verse');
            });
        }

        // seerah_events: chronological timeline scan
        if (Schema::hasTable('seerah_events')) {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->index(['hijri_year', 'hijri_month', 'hijri_day'], 'idx_seerah_chronological');
            });
        }

        // hadith_books: books within a collection sorted by Arabic title
        if (Schema::hasTable('hadith_books')) {
            Schema::table('hadith_books', function (Blueprint $table) {
                $table->index(['collection_id', 'title_ar'], 'idx_hadith_book_collection_title');
            });
        }

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

        if (Schema::hasTable('quran_verse_translations')) {
            Schema::table('quran_verse_translations', function (Blueprint $table) {
                $table->dropIndex('idx_qvt_lang_verse');
            });
        }

        if (Schema::hasTable('seerah_events')) {
            Schema::table('seerah_events', function (Blueprint $table) {
                $table->dropIndex('idx_seerah_chronological');
            });
        }

        if (Schema::hasTable('hadith_books')) {
            Schema::table('hadith_books', function (Blueprint $table) {
                $table->dropIndex('idx_hadith_book_collection_title');
            });
        }

        if (Schema::hasTable('personal_access_tokens')) {
            Schema::table('personal_access_tokens', function (Blueprint $table) {
                $table->dropIndex('idx_pat_tokenable_last_used');
            });
        }
    }
};
