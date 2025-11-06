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
     * Articles table with JSON multi-language support
     * Searchable fields moved to article_metadata table
     */
    public function up(): void
    {
        Schema::create('articles', function (Blueprint $table) {
            $table->id();

            // Unified slug (auto-generated from English title, same for all languages)
            $table->string('slug')->unique()->index();

            // Media
            $table->string('thumb')->nullable()->comment('Thumbnail image for cards/grid');

            // Localized Content Fields (JSON)
            $table->json('title')->comment('Multi-language title: {ar, en, fr, tr, ur}');
            $table->json('description')->nullable()->comment('Multi-language description (WYSIWYG): {ar, en, fr, tr, ur}');
            $table->json('excerpt')->nullable()->comment('Multi-language excerpt (plain text): {ar, en, fr, tr, ur}');
            $table->json('content')->comment('Multi-language content: {ar: {body, toc, summarize_points, summary}, en: {...}}');

            // Publishing - Single publish date
            $table->date('publish_date')->nullable()->comment('Publication date (Gregorian calendar)');
            $table->string('publish_date_hijri')->nullable()->comment('Publication date (Hijri calendar)');
            $table->timestamp('published_at')->nullable();

            // Relationships
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('writer')->nullable()->constrained('users')->onDelete('set null')->comment('Admin writer');

            // Engagement & Analytics
            $table->unsignedBigInteger('views')->default(0);
            $table->integer('read_time')->default(0)->comment('Reading time in minutes');

            // References & Statistics (JSON)
            $table->json('references')->nullable()->comment('[{name, description, link}]');
            $table->json('statistics')->nullable()->comment('[{key, value}]');

            // Status & Flags
            $table->enum('status', ['draft', 'under_review', 'published', 'archived', 'rejected'])->default('draft');
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_pinned')->default(false);

            // SEO (JSON - localized)
            $table->json('seo_metadata')->nullable()->comment('Multi-language SEO: {ar: {title, description, keywords, og_*}, en: {...}}');

            // Timestamps
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('category_id');
            $table->index('writer');
            $table->index('publish_date');
            $table->index('published_at');
            $table->index(['status', 'publish_date']);
            $table->index(['is_featured', 'is_pinned']);
            $table->index('views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
