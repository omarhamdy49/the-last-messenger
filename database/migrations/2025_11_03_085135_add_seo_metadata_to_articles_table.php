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
     * Add enhanced SEO and social media metadata fields to articles table.
     * These fields support multi-language content and social media optimization.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Add meta_description (JSON for multi-language support)
            if (!Schema::hasColumn('articles', 'meta_description')) {
                $table->json('meta_description')->nullable()->after('og_image')
                    ->comment('Multi-language meta descriptions for SEO (JSON: {ar, en, fr, tr, ur})');
            }

            // Add keywords (JSON array for flexibility)
            if (!Schema::hasColumn('articles', 'keywords')) {
                $table->json('keywords')->nullable()->after('meta_description')
                    ->comment('Array of SEO keywords/tags (JSON array)');
            }

            // Add share_text (JSON for multi-language social media share text)
            if (!Schema::hasColumn('articles', 'share_text')) {
                $table->json('share_text')->nullable()->after('keywords')
                    ->comment('Custom social media share text (JSON: {ar, en, fr, tr, ur})');
            }

            // Add social_image (separate from og_image for more control)
            if (!Schema::hasColumn('articles', 'social_image')) {
                $table->string('social_image')->nullable()->after('share_text')
                    ->comment('Specific social media preview image (optimized for social platforms)');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop columns in reverse order
            if (Schema::hasColumn('articles', 'social_image')) {
                $table->dropColumn('social_image');
            }
            if (Schema::hasColumn('articles', 'share_text')) {
                $table->dropColumn('share_text');
            }
            if (Schema::hasColumn('articles', 'keywords')) {
                $table->dropColumn('keywords');
            }
            if (Schema::hasColumn('articles', 'meta_description')) {
                $table->dropColumn('meta_description');
            }
        });
    }
};
