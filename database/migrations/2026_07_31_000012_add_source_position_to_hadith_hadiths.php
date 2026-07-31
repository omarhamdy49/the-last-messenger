<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The importer needs a key that is unique for EVERY record, including the ones
 * sunnah.com gives no permalink and no distinct number.
 *
 * Muwatta Malik book 11 holds five records but only four distinguishable ones:
 * two are both "Book 11, Hadith 3", and the second carries no urn. Keyed on
 * urn-then-number, the pair merged and 51 Malik records were lost.
 *
 * `source_position` is the record's ordinal on its source book page — always
 * present, always unique within a book, and stable across re-crawls of the same
 * page. It is an import key only; nothing user-facing reads it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->unsignedInteger('source_position')->nullable()->after('in_book_number');
        });

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            $table->unique(['book_id', 'source_position'], 'hadith_hadiths_book_id_source_position_unique');
        });
    }

    public function down(): void
    {
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            if ($this->indexExists('hadith_hadiths_book_id_source_position_unique')) {
                $table->dropUnique('hadith_hadiths_book_id_source_position_unique');
            }
            $table->dropColumn('source_position');
        });
    }

    private function indexExists(string $name): bool
    {
        return DB::table('information_schema.STATISTICS')
            ->where('TABLE_SCHEMA', DB::getDatabaseName())
            ->where('TABLE_NAME', 'hadith_hadiths')
            ->where('INDEX_NAME', $name)
            ->exists();
    }
};
