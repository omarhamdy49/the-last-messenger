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
        Schema::create('hadith_grading_levels', function (Blueprint $table) {
            $table->id();
            $table->string('grade_ar', 50)->comment('Grade in Arabic (e.g., صحيح، حسن، ضعيف)');
            $table->string('grade_en', 50)->comment('Grade in English (e.g., Sahih, Hasan, Da\'if)');
            $table->text('description')->nullable()->comment('Grade description');
            $table->integer('rank')->nullable()->comment('Numeric ranking (1 = highest)');

            // Unique constraint
            $table->unique('grade_en', 'uq_hadith_grade_en');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_grading_levels ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_grading_levels');
    }
};
