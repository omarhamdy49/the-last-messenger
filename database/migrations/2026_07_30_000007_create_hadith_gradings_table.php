<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Source grade strings are free text with 400+ distinct values (130+ measured in a 3,000-record
     * sample). `raw_grade` keeps the original verbatim; `grade` is the normalised rollup. The enum
     * must never become the only record of a grading.
     */
    public function up(): void
    {
        Schema::create('hadith_gradings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->foreignId('scholar_id')->nullable()->constrained('hadith_scholars')->restrictOnDelete();

            $table->enum('grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown']);

            // Grading the chain is a different claim from grading the hadith: the source contains
            // both "Sahih" and "Sahih Isnād" / "Da'if in chain".
            $table->enum('scope', ['hadith', 'isnad'])->default('hadith');

            $table->string('raw_grade', 1000);
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur'])->nullable();

            $table->text('notes')->nullable();
            $table->string('source_reference')->nullable();
            $table->timestamps();

            $table->index('hadith_id');
            $table->index(['grade', 'hadith_id'], 'idx_hadith_gradings_grade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_gradings');
    }
};
