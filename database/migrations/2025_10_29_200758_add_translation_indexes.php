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
     * Adds performance indexes on frequently queried columns.
     * Note: Full-text search indexes for JSON fields already exist in base migrations.
     */
    public function up(): void
    {
        // Only run on MySQL/MariaDB
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Helper function to check if index exists
        $indexExists = function (string $table, string $indexName): bool {
            $indexes = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = ?", [$indexName]);

            return ! empty($indexes);
        };

        // Articles table - composite indexes for common query patterns
        Schema::table('articles', function (Blueprint $table) use ($indexExists) {
            if (! $indexExists('articles', 'idx_articles_category_published')) {
                $table->index(['category_id', 'published_at'], 'idx_articles_category_published');
            }
            if (! $indexExists('articles', 'idx_articles_user_status')) {
                $table->index(['user_id', 'status'], 'idx_articles_user_status');
            }
            if (! $indexExists('articles', 'idx_articles_series_published')) {
                $table->index(['series_id', 'published_at'], 'idx_articles_series_published');
            }
        });

        // Note: Most other tables already have adequate indexes in their base migrations.
        // Additional indexes can be added in future migrations as performance needs are identified.
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        // Drop added indexes
        Schema::table('articles', function (Blueprint $table) {
            $table->dropIndex('idx_articles_category_published');
            $table->dropIndex('idx_articles_user_status');
            $table->dropIndex('idx_articles_series_published');
        });
    }
};
