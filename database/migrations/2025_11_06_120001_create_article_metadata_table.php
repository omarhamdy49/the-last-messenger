<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Article metadata table for searchable fields per locale
     * This solves the search performance issue by having indexed searchable columns
     */
    public function up(): void
    {
        Schema::create('article_metadata', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade');
            $table->string('locale', 5)->comment('Language code: ar, en, fr, tr, ur');

            // Searchable fields (extracted from JSON for performance)
            $table->text('searchable_title')->comment('Plain text title for full-text search');
            $table->text('searchable_excerpt')->comment('Plain text excerpt for full-text search');

            $table->timestamps();

            // Indexes
            $table->index('article_id');
            $table->index('locale');
            $table->unique(['article_id', 'locale'], 'article_locale_unique');
        });

        // Add full-text search indexes (MySQL)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE article_metadata ADD FULLTEXT INDEX ft_search (searchable_title, searchable_excerpt)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_metadata');
    }
};
