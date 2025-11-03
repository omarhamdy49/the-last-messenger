<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Creates a pivot table for article cross-linking.
     * Allows articles to reference related articles for better content discovery.
     */
    public function up(): void
    {
        Schema::create('article_related', function (Blueprint $table) {
            $table->id();

            // Foreign keys to articles table
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->comment('The source article');

            $table->foreignId('related_article_id')
                ->constrained('articles')
                ->onDelete('cascade')
                ->comment('The related article being linked to');

            // Order column for custom sorting of related articles
            $table->integer('order')->default(0)->comment('Display order for related articles');

            $table->timestamps();

            // Indexes for performance
            $table->index('article_id');
            $table->index('related_article_id');
            $table->index('order');

            // Unique constraint to prevent duplicate relationships
            // An article cannot have the same related article multiple times
            $table->unique(['article_id', 'related_article_id'], 'unique_article_relation');

            // Composite index for efficient queries when fetching related articles
            $table->index(['article_id', 'order'], 'article_order_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_related');
    }
};
