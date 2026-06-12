<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_gradings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->foreignId('scholar_id')->constrained('hadith_scholars')->onDelete('cascade')->comment('Reference to scholar');
            $table->foreignId('grading_level_id')->constrained('hadith_grading_levels')->onDelete('cascade')->comment('Reference to grading level');
            $table->text('grading_notes')->nullable()->comment('Notes about the grading');
            $table->string('source_reference', 255)->nullable()->comment('Source reference');
            $table->date('date_evaluated')->nullable()->comment('Date of evaluation');

            // Indexes
            $table->index('hadith_id');
            $table->index('scholar_id');
            $table->index('grading_level_id');
            $table->unique(['hadith_id', 'scholar_id']);
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_gradings ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_gradings');
    }
};
