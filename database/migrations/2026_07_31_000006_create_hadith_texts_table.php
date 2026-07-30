<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §5 — one row per locale THAT HAS TEXT.
 *
 * English is absent for ~7% of records. `body` is NOT NULL, so an Arabic-only
 * hadith has exactly one row: a text row is omitted rather than written empty
 * (FR-015). Every read path falls back to Arabic.
 *
 * `search_body` is the ONLY full-text-indexed column. MySQL requires the
 * MATCH() column list to equal a FULLTEXT index exactly, so FULLTEXT (body,
 * search_body) could not serve MATCH(search_body); indexing both would also
 * index the same Arabic text twice, inflating term frequencies and distorting
 * relevance. It holds normalised Arabic for `ar` and the original body for
 * Latin-script locales.
 *
 * Requires innodb_ft_min_token_size = 2 (default 3 silently makes two-character
 * Arabic tokens unindexable — FR-012). The ngram parser must NOT be used: it is
 * CJK-oriented and wrong for Arabic.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_texts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);
            // As displayed, diacritics intact.
            $table->longText('body');
            $table->longText('search_body');
            $table->string('narrated_by', 500)->nullable();
            $table->text('sanad')->nullable();
            $table->longText('matn')->nullable();
            $table->string('translator', 255)->nullable();
            $table->string('source_url', 500)->nullable();
            $table->timestamps();

            $table->unique(['hadith_id', 'locale']);

            if (DB::getDriverName() === 'mysql') {
                $table->fullText('search_body', 'ft_hadith_texts_search');
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_texts');
    }
};
