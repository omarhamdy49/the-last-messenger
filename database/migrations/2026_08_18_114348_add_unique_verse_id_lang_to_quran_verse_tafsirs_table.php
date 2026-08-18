<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * DB-02: the real business key for a verse tafsir is (verse_id, lang) — one
 * tafsir per verse per language. The existing unique(verse_id, resource_id)
 * is bypassed whenever resource_id is NULL (MySQL treats NULLs as distinct),
 * which is how duplicates piled up. Dedup (keep the lowest id per pair)
 * before adding the index.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::statement(<<<'SQL'
            DELETE t1 FROM quran_verse_tafsirs t1
            INNER JOIN quran_verse_tafsirs t2
                ON t1.verse_id = t2.verse_id
               AND t1.lang = t2.lang
               AND t1.id > t2.id
        SQL);

        Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
            $table->unique(['verse_id', 'lang']);
        });
    }

    public function down(): void
    {
        Schema::table('quran_verse_tafsirs', function (Blueprint $table) {
            $table->dropUnique(['verse_id', 'lang']);
        });
    }
};
