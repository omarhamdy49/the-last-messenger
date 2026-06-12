<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add SEO and translation fields to articles table.
 * These columns support the SeoService and the Article model's
 * translatable content (via Spatie HasTranslations).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('slug')->nullable()->unique()->after('thumb');
            $table->json('title')->nullable()->after('slug');
            $table->json('excerpt')->nullable()->after('title');
            $table->json('content')->nullable()->after('excerpt');
            $table->json('seo_title')->nullable()->after('content');
            $table->json('seo_description')->nullable()->after('seo_title');
            $table->string('seo_keywords')->nullable()->after('seo_description');
            $table->string('featured_image')->nullable()->after('seo_keywords');
            $table->string('og_image')->nullable()->after('featured_image');
            $table->boolean('featured')->default(false)->after('og_image');
            $table->unsignedSmallInteger('reading_time_minutes')->default(0)->after('featured');
            $table->timestamp('published_at')->nullable()->after('reading_time_minutes');

            $table->index('slug');
            $table->index('featured');
            $table->index('published_at');
        });
    }

    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex(['slug']);
            $table->dropIndex(['featured']);
            $table->dropIndex(['published_at']);
            $table->dropUnique(['slug']);
            $table->dropColumn([
                'slug',
                'title',
                'excerpt',
                'content',
                'seo_title',
                'seo_description',
                'seo_keywords',
                'featured_image',
                'og_image',
                'featured',
                'reading_time_minutes',
                'published_at',
            ]);
        });
    }
};
