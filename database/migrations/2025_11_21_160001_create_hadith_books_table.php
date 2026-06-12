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
        Schema::create('hadith_books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->onDelete('cascade')->comment('Reference to collection');
            $table->string('title_ar', 255)->nullable()->comment('Book title in Arabic');
            $table->string('title_en', 255)->nullable()->comment('Book title in English');
            $table->string('slug_ar', 255)->nullable()->comment('Arabic slug for URLs');
            $table->string('slug_en', 255)->nullable()->comment('English slug for URLs');
            $table->string('chapter_title_ar', 255)->nullable()->comment('Chapter title in Arabic');
            $table->string('chapter_title_en', 255)->nullable()->comment('Chapter title in English');
            $table->timestamps();

            // Indexes
            $table->index('collection_id');
            $table->index('title_ar');
            $table->index('slug_ar');
            $table->index('slug_en');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_books ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }

        // Add FULLTEXT index
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE hadith_books ADD FULLTEXT INDEX ft_hadith_books_titles (title_ar, title_en, chapter_title_ar, chapter_title_en)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_books');
    }
};
