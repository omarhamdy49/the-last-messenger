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
        Schema::create('hadith_scholars', function (Blueprint $table) {
            $table->id();
            $table->string('name_ar', 255)->comment('Scholar name in Arabic');
            $table->string('name_en', 255)->nullable()->comment('Scholar name in English');
            $table->string('kunya', 255)->nullable()->comment('Kunya (e.g., Abu...)');
            $table->string('full_name', 255)->nullable()->comment('Full name');
            $table->string('school', 255)->nullable()->comment('School of thought (e.g., Shafi\'i, Hanbali)');
            $table->integer('birth_year_hijri')->nullable()->comment('Birth year (Hijri calendar)');
            $table->integer('death_year_hijri')->nullable()->comment('Death year (Hijri calendar)');
            $table->string('region', 255)->nullable()->comment('Region (e.g., Khorasan, Andalus)');
            $table->text('biography')->nullable()->comment('Biographical information');

            // Indexes
            $table->index(['name_ar', 'name_en'], 'idx_hadith_scholar_name');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_scholars ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_scholars ADD FULLTEXT INDEX ft_hadith_scholars (name_ar, name_en, biography)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_scholars');
    }
};
