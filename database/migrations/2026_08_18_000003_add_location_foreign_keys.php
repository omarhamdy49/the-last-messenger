<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Point the three place-bearing tables at the canonical vocabulary.
 *
 * ADDITIVE ONLY — this migration drops and alters nothing.
 *
 *  - `seerah_events.location` (free text) was removed by 005-seerah-schema-refactor;
 *    `location_id` is the only place field on an event.
 *
 *  - `muslims.birth_place` stays until nothing reads it, which is a separate
 *    later decision, not part of this feature.
 *
 *  - Quran chapters: `quran_chapters.revelation_location_id` is created by
 *    2026_09_18_000001_create_quran_chapters_tables (006-quran-qf-refactor), because
 *    that table does not exist at this date. `revelation_place` (makkah/madinah) stays
 *    as the period classification; the location is the coordinate.
 *
 * Cross-module foreign keys are permitted: constitution IV allows database keys
 * to cross module boundaries and forbids only the PHP import. The existing
 * hadith_narrators.narrator_id → muslims is the same shape.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seerah_events', function (Blueprint $table): void {
            $table->foreignId('location_id')->nullable()->after('sm_id')
                ->constrained('locations')->nullOnDelete();
        });

        Schema::table('muslims', function (Blueprint $table): void {
            $table->foreignId('birth_place_id')->nullable()->after('birth_place')
                ->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('muslims', function (Blueprint $table): void {
            $table->dropForeign(['birth_place_id']);
            $table->dropColumn('birth_place_id');
        });

        Schema::table('seerah_events', function (Blueprint $table): void {
            $table->dropForeign(['location_id']);
            $table->dropColumn('location_id');
        });
    }
};
