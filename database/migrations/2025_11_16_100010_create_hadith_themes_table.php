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
        Schema::create('hadith_hadith_themes', function (Blueprint $table) {
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->onDelete('cascade')->comment('Reference to theme');

            // Composite primary key
            $table->primary(['hadith_id', 'hadith_theme_id']);

            // Indexes
            $table->index('hadith_id', 'idx_hadith_hadith_themes_hadith');
            $table->index('hadith_theme_id', 'idx_hadith_hadith_themes_theme');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_hadith_themes ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_hadith_themes');
    }
};
