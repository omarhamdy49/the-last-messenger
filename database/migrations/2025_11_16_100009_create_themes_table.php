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
        Schema::create('hadith_themes', function (Blueprint $table) {
            $table->id();
            $table->string('theme_en', 100)->nullable()->comment('Theme/subject in English');
            $table->string('theme_ar', 100)->nullable()->comment('Theme/subject in Arabic');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_themes ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_themes ADD FULLTEXT INDEX ft_hadith_themes (theme_en, theme_ar)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_themes');
    }
};
