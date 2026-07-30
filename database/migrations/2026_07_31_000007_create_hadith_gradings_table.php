<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §6 — one row per CLAIM (FR-002, FR-003, FR-004).
 *
 * One source string may assert several claims:
 *   "Sanad Da'if wal-Hadith Hasan" → (daif, isnad) + (hasan, hadith)
 *   "1: Hasan<br>2: Da'if"        → two rows with part_label "1" and "2"
 * Both share one `raw_grade`. A normaliser returning a single verdict silently
 * discards one claim per such record.
 *
 * `raw_grade` is TEXT, not varchar: the prose cases run long enough to truncate.
 * It is never discarded, because the previous importer lowercased each distinct
 * raw string into a taxonomy row and produced 566 junk entries.
 *
 * The source's `authenticated_by` field must produce NO rows here — it names the
 * compiler of the collection the hadith already belongs to, which is membership,
 * not an independent verdict (FR-006). Treating it as one previously generated
 * 9,379 + 8,863 bogus rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_gradings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->restrictOnDelete();
            $table->enum('grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown']);
            // A verdict on the chain is a different claim from one on the hadith.
            $table->enum('scope', ['hadith', 'isnad'])->default('hadith');
            // Which part of a multi-verdict string this row addresses.
            $table->string('part_label', 100)->nullable();
            // Lower wins.
            $table->unsignedTinyInteger('authority_rank')->default(100);
            $table->text('raw_grade');
            // The language the raw string was written in.
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur'])->nullable();
            $table->text('notes')->nullable();
            $table->string('source_reference', 255)->nullable();
            $table->timestamps();

            // hadith_id already gets an index from its foreign key.
            $table->index(['grade', 'hadith_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_gradings');
    }
};
