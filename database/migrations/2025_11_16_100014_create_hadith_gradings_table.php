<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_gradings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->foreignId('scholar_id')->constrained('hadith_scholars')->onDelete('cascade')->comment('Reference to scholar');
            $table->foreignId('grading_level_id')->constrained('hadith_grading_levels')->onDelete('restrict')->comment('Reference to grading level');
            $table->text('grading_notes')->nullable()->comment('Grading notes/reasoning');
            $table->string('source_reference', 255)->nullable()->comment('Source reference (e.g., Silsilat al-Ahadith al-Sahihah 1/23)');
            $table->date('date_evaluated')->nullable()->comment('Date of evaluation');

            // Indexes
            $table->index('hadith_id', 'idx_hadith_gradings_hadith');
            $table->index('scholar_id', 'idx_hadith_gradings_scholar');
            $table->index('grading_level_id', 'idx_hadith_gradings_level');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_gradings ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_gradings');
    }
};
