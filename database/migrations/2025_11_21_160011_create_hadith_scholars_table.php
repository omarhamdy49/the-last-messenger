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
            $table->string('school', 255)->nullable()->comment('School of thought');
            $table->integer('birth_year_hijri')->nullable()->comment('Birth year (Hijri)');
            $table->integer('death_year_hijri')->nullable()->comment('Death year (Hijri)');
            $table->string('region', 255)->nullable()->comment('Region');
            $table->text('biography')->nullable()->comment('Biography');

            // Indexes
            $table->index('name_ar');
            $table->index('name_en');
            $table->index('school');
            $table->index('death_year_hijri');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_scholars ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_scholars ADD FULLTEXT INDEX ft_hadith_scholars_names (name_ar, name_en, full_name)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_scholars');
    }
};
