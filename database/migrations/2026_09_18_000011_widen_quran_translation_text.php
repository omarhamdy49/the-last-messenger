<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * `quran_translations.text` was TEXT — 65,535 *bytes*, which is roughly 21k characters
 * once the text is Arabic or accented Latin. Several Quran Foundation translations carry
 * inline commentary well past that, and the sync failed the whole batch with
 * "Data too long for column 'text'" rather than writing a truncated ayah.
 *
 * Tafsirs already use LONGTEXT for the same reason; translations now match, so no verse
 * text can be rejected for length again.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quran_translations') || DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement('ALTER TABLE quran_translations MODIFY text LONGTEXT NOT NULL');
    }

    public function down(): void
    {
        if (! Schema::hasTable('quran_translations') || DB::getDriverName() !== 'mysql') {
            return;
        }

        // Rows longer than TEXT would be truncated, so refuse rather than lose an ayah.
        $longest = (int) DB::table('quran_translations')->selectRaw('MAX(LENGTH(text)) AS n')->value('n');

        if ($longest > 65535) {
            throw new RuntimeException("Cannot narrow quran_translations.text: a row holds {$longest} bytes.");
        }

        DB::statement('ALTER TABLE quran_translations MODIFY text TEXT NOT NULL');
    }
};
