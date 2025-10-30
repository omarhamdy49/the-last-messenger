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
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Author
            $table->foreignId('category_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('series_id')->nullable()->constrained()->onDelete('set null');
            $table->json('title'); // Multi-language support
            $table->json('excerpt')->nullable();
            $table->json('content'); // Long text for multi-language content
            $table->string('featured_image')->nullable();
            $table->integer('reading_time_minutes')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->unsignedBigInteger('favorites_count')->default(0);
            $table->enum('status', ['draft', 'published', 'archived'])->default('draft');
            $table->timestamp('published_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('slug');
            $table->index('user_id');
            $table->index('category_id');
            $table->index('series_id');
            $table->index('status');
            $table->index('published_at');
            $table->index('created_at');
            $table->index('views_count');
        });

        // Add generated columns for full-text search (MySQL only)
        // Since title and excerpt are JSON, we need generated columns for full-text indexing
        if (config('database.default') === 'mysql') {
            // Create generated columns for the default language (Arabic)
            DB::statement("ALTER TABLE articles ADD COLUMN title_searchable TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(title, '$.ar'))) STORED");
            DB::statement("ALTER TABLE articles ADD COLUMN excerpt_searchable TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(excerpt, '$.ar'))) STORED");

            // Add full-text index on generated columns
            DB::statement('ALTER TABLE articles ADD FULLTEXT fulltext_search (title_searchable, excerpt_searchable)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
