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
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->foreignId('hadith_theme_id')->constrained('hadith_themes')->onDelete('cascade')->comment('Reference to theme');

            // Indexes
            $table->index('hadith_id');
            $table->index('hadith_theme_id');
            $table->unique(['hadith_id', 'hadith_theme_id']);
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_hadith_themes ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_hadith_themes');
    }
};
