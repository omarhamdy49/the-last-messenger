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
 *  - `seerah_events.location` stays. It holds editorial prose, not place names
 *    ("حمراء الأسد على نحو 8 أميال جنوب المدينة"), 215 distinct values across 279
 *    filled rows. Replacing it would delete content no place record can rebuild,
 *    and would churn ~30 call sites. See research.md R-02.
 *
 *  - `muslims.birth_place` stays until nothing reads it, which is a separate
 *    later decision, not part of this feature.
 *
 *  - `quran_surahs.revelation_place` stays PERMANENTLY. RevelationPlace::label()
 *    returns مكية/مدنية — a classification of the *period* of revelation, not a
 *    coordinate — and it is read in 14 files. See research.md R-07.
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
            $table->foreignId('location_id')->nullable()->after('location')
                ->constrained('locations')->nullOnDelete();
        });

        Schema::table('muslims', function (Blueprint $table): void {
            $table->foreignId('birth_place_id')->nullable()->after('birth_place')
                ->constrained('locations')->nullOnDelete();
        });

        Schema::table('quran_surahs', function (Blueprint $table): void {
            $table->foreignId('revelation_location_id')->nullable()->after('revelation_place')
                ->constrained('locations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('quran_surahs', function (Blueprint $table): void {
            $table->dropForeign(['revelation_location_id']);
            $table->dropColumn('revelation_location_id');
        });

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
