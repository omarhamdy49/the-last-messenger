<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Reverts DB-03 (2026_08_18_1143489/…49), which re-added unique(verse_id, lang)
 * to the verse translation/tafsir tables. That key caps a verse at ONE row per
 * language, which silently defeats the multi-edition design introduced by
 * 2026_06_15_1300002 — MySQL's ON DUPLICATE KEY UPDATE fires on *any* unique
 * violation, so importing a second English edition updated the first edition's
 * row in place instead of inserting its own. Symptom: `quran:import-editions`
 * reports "Imported 6236 entries" for all 26 editions while the table ends up
 * holding one edition per language.
 *
 * (verse_id, resource_id) is the correct business key and stays.
 */
return new class extends Migration
{
    /** @var list<array{table: string, index: string}> */
    private const TARGETS = [
        ['table' => 'quran_verse_translations', 'index' => 'quran_verse_translations_verse_id_lang_unique'],
        ['table' => 'quran_verse_tafsirs', 'index' => 'quran_verse_tafsirs_verse_id_lang_unique'],
    ];

    public function up(): void
    {
        // SQLite (test driver) never had the index — the June migration skipped
        // the swap there too, and correctness is enforced by the import layer.
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        foreach (self::TARGETS as $target) {
            if ($this->indexExists($target['table'], $target['index'])) {
                DB::statement("ALTER TABLE `{$target['table']}` DROP INDEX `{$target['index']}`");
            }
        }
    }

    public function down(): void
    {
        // Deliberately not restoring the index: re-adding it re-breaks
        // multi-edition imports. Roll back DB-03 instead if that is truly wanted.
    }

    private function indexExists(string $table, string $index): bool
    {
        return DB::selectOne(
            'SELECT 1 AS present FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ? LIMIT 1',
            [$table, $index],
        ) !== null;
    }
};
