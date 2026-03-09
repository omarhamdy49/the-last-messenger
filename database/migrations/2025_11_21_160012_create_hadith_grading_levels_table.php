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
            $table->string('grade_ar', 50)->comment('Grading level in Arabic (e.g., صحيح)');
            $table->string('grade_en', 50)->unique()->comment('Grading level in English (e.g., Sahih)');
            $table->text('description')->nullable()->comment('Description of this grading level');
            $table->integer('rank')->nullable()->comment('Rank (1=highest authenticity)');

            // Indexes
            $table->index('rank');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_grading_levels ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_grading_levels');
    }
};
