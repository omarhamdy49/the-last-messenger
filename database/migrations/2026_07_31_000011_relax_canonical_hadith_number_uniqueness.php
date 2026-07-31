<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §4 called `UNIQUE (collection_id, hadith_number)` "the canonical
 * identity". The real corpus disproves it, in the same way it disproved the
 * chapter-number key: Jami` at-Tirmidhi lists 4,053 records under only 3,997
 * distinct numbers. 52 numbers cover two genuinely different hadiths — 1322 is
 * both `urn 613700` ("Go and judge between the people") and `urn 613710` ("The
 * judges are three"), with different Arabic, different English and different
 * permalinks.
 *
 * Under the unique key the importer silently kept the first and discarded the
 * second: 56 hadiths of Tirmidhi alone never reached the database, and nothing
 * reported it.
 *
 * The key becomes a plain index — it still serves the canonical lookup, which
 * now resolves to the first match in reading order, exactly as sunnah.com's own
 * /tirmidhi:1322 does. Row identity moves to `sunnah_urn`, which is UNIQUE
 * already and is populated for every record measured so far.
 */
return new class extends Migration
{
    public function up(): void
    {
        // The replacement index is created BEFORE the unique one is dropped.
        // The composite foreign key (book_id, collection_id) relies on an index
        // led by collection_id, and MySQL refuses to drop the last one that
        // satisfies it: "Cannot drop index … needed in a foreign key constraint".
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            if (! $this->indexExists('hadith_hadiths_collection_id_hadith_number_index')) {
                $table->index(['collection_id', 'hadith_number'], 'hadith_hadiths_collection_id_hadith_number_index');
            }
        });

        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            if ($this->indexExists('hadith_hadiths_collection_id_hadith_number_unique')) {
                $table->dropUnique('hadith_hadiths_collection_id_hadith_number_unique');
            }
        });
    }

    public function down(): void
    {
        // Deliberately not restored: the real data cannot satisfy it, so a
        // rollback that re-adds the unique key would fail on any populated
        // database. The index is dropped and nothing more.
        Schema::table('hadith_hadiths', function (Blueprint $table): void {
            if ($this->indexExists('hadith_hadiths_collection_id_hadith_number_index')) {
                $table->dropIndex('hadith_hadiths_collection_id_hadith_number_index');
            }
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
