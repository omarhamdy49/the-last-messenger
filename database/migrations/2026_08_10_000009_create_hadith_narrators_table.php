<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §9 — the chain of narration.
 *
 * `narrator_id` points at `muslims`: a narrator is a person, not a separate
 * record. The source's own spelling is kept alongside the link because it is
 * the evidence by which the link can be audited later.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_narrators', function (Blueprint $table): void {
            // RESTRICT, not cascade. MySQL refuses a STORED generated column
            // derived from a column that carries ON DELETE CASCADE, and the
            // generated column below is the only thing that can enforce "one
            // primary narrator per hadith" at the database level. Deleting a
            // hadith therefore requires deleting its chain first, which the
            // repository does inside the same transaction.
            $table->foreignId('hadith_id')->constrained('hadiths')->restrictOnDelete();
            $table->foreignId('narrator_id')->constrained('muslims')->restrictOnDelete();

            // SMALLINT, not tinyint — the chain needs the width.
            $table->unsignedSmallInteger('position');

            // Renamed from `primary`, a reserved word in MySQL 8.
            $table->boolean('is_primary')->default(false);

            $table->string('source_name_ar', 255)->nullable();
            $table->string('source_name_en', 255)->nullable();
            $table->timestamps();

            $table->primary(['hadith_id', 'position']);

            // Primary-narrator profile listings.
            $table->index(['narrator_id', 'is_primary', 'hadith_id']);

            // Deliberately NO unique(hadith_id, narrator_id): a narrator may
            // legitimately recur in a chain. Validate against the corpus before
            // constraining — assuming uniqueness is what cost 56 records once.
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            // One primary narrator per hadith. Blueprint cannot express a
            // STORED generated column with a CASE, and a UNIQUE index permits
            // multiple NULLs in MySQL — so non-primary rows never collide
            // while two primaries on one hadith do.
            DB::statement(
                'ALTER TABLE hadith_narrators '.
                'ADD COLUMN primary_hadith_id BIGINT UNSIGNED '.
                'GENERATED ALWAYS AS (CASE WHEN is_primary = 1 THEN hadith_id ELSE NULL END) STORED, '.
                'ADD UNIQUE KEY uq_hadith_narrators_one_primary (primary_hadith_id)'
            );
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_narrators');
    }
};
