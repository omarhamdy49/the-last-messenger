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
        Schema::create('hadith_narrators', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 255)->comment('Narrator name in Arabic');
            $table->string('name_en', 255)->nullable()->comment('Narrator name in English');
            $table->string('full_name_ar', 500)->nullable()->comment('Full name in Arabic');
            $table->string('full_name_en', 500)->nullable()->comment('Full name in English');
            $table->string('kunya', 255)->nullable()->comment('Kunya (e.g., Abu...)');
            $table->integer('birth_year_hijri')->nullable()->comment('Birth year (Hijri)');
            $table->integer('death_year_hijri')->nullable()->comment('Death year (Hijri)');
            $table->string('reliability_grade', 100)->nullable()->comment('Reliability grade (e.g., Thiqah)');
            $table->text('biography')->nullable()->comment('Biographical information');
            $table->string('school', 100)->nullable()->comment('School of thought');
            $table->string('region', 100)->nullable()->comment('Region (e.g., Khorasan)');
            $table->timestamps();

            // Indexes
            $table->index('name_ar');
            $table->index('name_en');
            $table->index('reliability_grade');
            $table->index('death_year_hijri');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_narrators ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_narrators ADD FULLTEXT INDEX ft_hadith_narrators_names (name_ar, name_en, full_name_ar, full_name_en, kunya)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_narrators');
    }
};
