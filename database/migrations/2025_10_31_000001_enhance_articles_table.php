<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table exists and if column already exists
        if (!Schema::hasTable('articles')) {
            return;
        }

        Schema::table('articles', function (Blueprint $table) {
            // Add featured flag if it doesn't exist
            if (!Schema::hasColumn('articles', 'featured')) {
                $table->boolean('featured')->default(false)->after('status');
            }

            // Add article management fields if they don't exist
            if (!Schema::hasColumn('articles', 'order')) {
                $table->integer('order')->default(0)->after('featured');
            }

            if (!Schema::hasColumn('articles', 'is_pinned')) {
                $table->boolean('is_pinned')->default(false)->after('order');
            }

            // Add publishing workflow fields if they don't exist
            if (!Schema::hasColumn('articles', 'submitted_at')) {
                $table->timestamp('submitted_at')->nullable()->after('published_at');
            }

            if (!Schema::hasColumn('articles', 'submitter_id')) {
                $table->foreignId('submitter_id')->nullable()->constrained('users')->onDelete('set null')->after('submitted_at');
            }

            if (!Schema::hasColumn('articles', 'reviewed_at')) {
                $table->timestamp('reviewed_at')->nullable()->after('submitter_id');
            }

            // Add reviewer_id if it doesn't exist
            if (!Schema::hasColumn('articles', 'reviewer_id')) {
                $table->foreignId('reviewer_id')->nullable()->constrained('users')->onDelete('set null')->after('reviewed_at');
            }

            // Add SEO fields if they don't exist
            if (!Schema::hasColumn('articles', 'seo_title')) {
                $table->string('seo_title', 255)->nullable()->after('reviewer_id');
            }

            if (!Schema::hasColumn('articles', 'seo_description')) {
                $table->text('seo_description')->nullable()->after('seo_title');
            }

            if (!Schema::hasColumn('articles', 'seo_keywords')) {
                $table->text('seo_keywords')->nullable()->after('seo_description');
            }

            if (!Schema::hasColumn('articles', 'og_image')) {
                $table->string('og_image')->nullable()->after('seo_keywords');
            }

            // Add indexes
            $table->index('featured');
            $table->index('is_pinned');
            $table->index('order');
            $table->index('reviewer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop SEO columns if they exist
            if (Schema::hasColumn('articles', 'seo_title')) {
                $table->dropColumn('seo_title');
            }
            if (Schema::hasColumn('articles', 'seo_description')) {
                $table->dropColumn('seo_description');
            }
            if (Schema::hasColumn('articles', 'seo_keywords')) {
                $table->dropColumn('seo_keywords');
            }
            if (Schema::hasColumn('articles', 'og_image')) {
                $table->dropColumn('og_image');
            }

            // Drop foreign key columns if they exist
            if (Schema::hasColumn('articles', 'reviewer_id')) {
                $table->dropForeignKeyIfExists(['reviewer_id']);
                $table->dropColumn('reviewer_id');
            }
            if (Schema::hasColumn('articles', 'submitter_id')) {
                $table->dropForeignKeyIfExists(['submitter_id']);
                $table->dropColumn('submitter_id');
            }

            // Drop timestamp columns if they exist
            if (Schema::hasColumn('articles', 'reviewed_at')) {
                $table->dropColumn('reviewed_at');
            }
            if (Schema::hasColumn('articles', 'submitted_at')) {
                $table->dropColumn('submitted_at');
            }

            // Drop management columns if they exist
            if (Schema::hasColumn('articles', 'is_pinned')) {
                $table->dropColumn('is_pinned');
            }
            if (Schema::hasColumn('articles', 'order')) {
                $table->dropColumn('order');
            }
            if (Schema::hasColumn('articles', 'featured')) {
                $table->dropColumn('featured');
            }
        });
    }
};
