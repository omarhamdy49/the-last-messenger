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
     * Adds author, engagement tracking, and content management fields to articles table.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Author & Co-Authors
            // author_id - primary article author (separate from user_id for content attribution)
            $table->foreignId('author_id')
                ->nullable()
                ->after('user_id')
                ->constrained('users')
                ->onDelete('set null');

            // co_authors - array of additional author user IDs (JSON)
            $table->json('co_authors')
                ->nullable()
                ->after('author_id')
                ->comment('Array of co-author user IDs');

            // Reading Time Override
            // reading_time_override - manual override for reading time calculation
            $table->integer('reading_time_override')
                ->nullable()
                ->after('reading_time_minutes')
                ->comment('Manual reading time in minutes (overrides auto-calculation)');

            // Engagement Tracking
            // last_viewed_at - timestamp of most recent view
            $table->timestamp('last_viewed_at')
                ->nullable()
                ->after('views_count')
                ->comment('Timestamp of most recent article view');

            // Comment Management
            // comments_enabled - whether comments are allowed on this article
            $table->boolean('comments_enabled')
                ->default(true)
                ->after('last_viewed_at')
                ->comment('Enable/disable comments for this article');

            // comment_moderation - comment approval workflow
            $table->enum('comment_moderation', ['auto_approve', 'manual_review', 'disabled'])
                ->default('manual_review')
                ->after('comments_enabled')
                ->comment('Comment moderation strategy');

            // Content Display Options
            // show_toc - whether to display table of contents
            $table->boolean('show_toc')
                ->default(true)
                ->after('comment_moderation')
                ->comment('Show table of contents in article');

            // References & Citations
            // references - bibliography/sources (JSON array)
            $table->json('references')
                ->nullable()
                ->after('show_toc')
                ->comment('Bibliography and source citations');

            // Content Warning & Disclaimer
            // has_content_warning - flag for sensitive content
            $table->boolean('has_content_warning')
                ->default(false)
                ->after('references')
                ->comment('Article contains sensitive content');

            // disclaimer_text - custom disclaimer for this article
            $table->text('disclaimer_text')
                ->nullable()
                ->after('has_content_warning')
                ->comment('Custom disclaimer text');

            // Content Management
            // created_by - user who created this article record
            $table->foreignId('created_by')
                ->nullable()
                ->after('disclaimer_text')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User who created this article');

            // last_edited_by - user who last modified this article
            $table->foreignId('last_edited_by')
                ->nullable()
                ->after('created_by')
                ->constrained('users')
                ->onDelete('set null')
                ->comment('User who last edited this article');

            // Indexes for performance
            $table->index('author_id', 'articles_author_id_index');
            $table->index('views_count', 'articles_views_count_performance_index');
            $table->index('last_viewed_at', 'articles_last_viewed_at_index');
            $table->index('comments_enabled', 'articles_comments_enabled_index');
            $table->index('has_content_warning', 'articles_has_content_warning_index');
            $table->index('created_by', 'articles_created_by_index');
            $table->index('last_edited_by', 'articles_last_edited_by_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            // Drop indexes first
            $table->dropIndex('articles_author_id_index');
            $table->dropIndex('articles_views_count_performance_index');
            $table->dropIndex('articles_last_viewed_at_index');
            $table->dropIndex('articles_comments_enabled_index');
            $table->dropIndex('articles_has_content_warning_index');
            $table->dropIndex('articles_created_by_index');
            $table->dropIndex('articles_last_edited_by_index');

            // Drop foreign key columns
            $table->dropForeign(['author_id']);
            $table->dropForeign(['created_by']);
            $table->dropForeign(['last_edited_by']);

            // Drop all added columns
            $table->dropColumn([
                'author_id',
                'co_authors',
                'reading_time_override',
                'last_viewed_at',
                'comments_enabled',
                'comment_moderation',
                'show_toc',
                'references',
                'has_content_warning',
                'disclaimer_text',
                'created_by',
                'last_edited_by',
            ]);
        });
    }
};
