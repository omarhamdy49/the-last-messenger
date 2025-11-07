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
        Schema::create('article_locale', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade')->comment('Article reference');
            $table->string('locale', 5)->comment('Language code: ar, en, fr, tr, ur');
            $table->text('title')->comment('Article title');
            $table->string('slug')->comment('URL-friendly slug');
            $table->text('description')->nullable()->comment('Article description');
            $table->text('excerpt')->nullable()->comment('Short excerpt for listing pages - searchable');
            $table->json('content')->nullable()->comment('Article content: {toc: [], body: string, summary: string}');
            $table->json('seo_metadata')->nullable()->comment('SEO data: {meta_title, meta_description, keywords, og_image}');
            $table->boolean('is_ready')->default(false)->comment('Translation completion status');
            $table->timestamps();

            // Indexes
            $table->index('article_id');
            $table->index('locale');
            $table->index('slug');
            $table->index('is_ready');
            $table->unique(['article_id', 'locale']);
        });

        // Add full-text search index on title and excerpt (MySQL only)
        if (config('database.default') === 'mysql') {
            DB::statement('ALTER TABLE article_locale ADD FULLTEXT INDEX ft_search (title, excerpt)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_locale');
    }
};
