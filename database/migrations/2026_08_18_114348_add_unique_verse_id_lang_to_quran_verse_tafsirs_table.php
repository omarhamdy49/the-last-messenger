<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;

/**
 * DB-03, NEUTRALISED (2026-09-05).
 *
 * This migration used to dedup quran_verse_tafsirs down to one row per
 * (verse_id, lang) and add unique(verse_id, lang). That key caps a verse at one
 * row per language, which defeats the multi-edition design of
 * 2026_06_15_1300002: MySQL fires ON DUPLICATE KEY UPDATE on *any* unique
 * violation, so a second edition in the same language overwrote the first
 * instead of inserting its own row.
 *
 * The body is intentionally empty rather than deleted, so already-migrated
 * databases keep their history row. 2026_09_05_000000 drops the index where it
 * was applied. The real key is (verse_id, resource_id).
 */
return new class extends Migration
{
    public function up(): void
    {
        // No-op. See the class docblock.
    }

    public function down(): void
    {
        // No-op.
    }
};
