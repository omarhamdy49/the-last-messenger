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
        Schema::create('user_engagement_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Overall metrics
            $table->integer('total_views')->default(0);
            $table->integer('total_likes')->default(0);
            $table->integer('total_shares')->default(0);
            $table->integer('total_bookmarks')->default(0);
            $table->integer('total_comments')->default(0);
            $table->integer('total_downloads')->default(0);
            $table->integer('total_searches')->default(0);

            // Calculated metrics
            $table->integer('engagement_score')->default(0); // Weighted score
            $table->integer('streak_days')->default(0); // Consecutive active days
            $table->timestamp('last_activity_at')->nullable(); // Last interaction timestamp
            $table->timestamp('streak_started_at')->nullable(); // When current streak started

            // Time-based metrics
            $table->integer('total_time_spent_seconds')->default(0); // Total time on platform
            $table->integer('avg_session_duration_seconds')->default(0); // Average session length

            // Period-specific counters (reset monthly)
            $table->integer('monthly_views')->default(0);
            $table->integer('monthly_interactions')->default(0);
            $table->date('metrics_month')->nullable(); // Track which month counters are for

            $table->timestamps();

            // Indexes
            $table->unique('user_id');
            $table->index('engagement_score'); // For leaderboards
            $table->index('last_activity_at'); // For activity tracking
            $table->index('streak_days'); // For streak leaderboards
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_engagement_metrics');
    }
};
