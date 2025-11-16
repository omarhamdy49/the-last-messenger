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
        Schema::create('hadith_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('keyword_ar', 100)->nullable()->comment('Keyword in Arabic');
            $table->string('keyword_en', 100)->nullable()->comment('Keyword in English');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_keywords ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_keywords ADD FULLTEXT INDEX ft_hadith_keywords (keyword_ar, keyword_en)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_keywords');
    }
};
