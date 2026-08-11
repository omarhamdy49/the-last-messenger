<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Raw, deduplicated media view events (programs, episodes, live channels).
 *
 * Written by the queued RecordMediaViewJob (one row per visitor per item per
 * hour), aggregated by media:compute-trending into time-windowed trending
 * scores and admin statistics, pruned after 90 days. Deliberately lean:
 * no updated_at, no FKs — events must stay cheap to write and may outlive
 * the users/items they reference.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_view_events', function (Blueprint $table): void {
            $table->id();
            $table->string('viewable_type', 20); // MediaViewableType value
            $table->unsignedBigInteger('viewable_id');
            $table->unsignedBigInteger('user_id')->nullable(); // null = guest
            $table->string('visitor_hash', 40);
            $table->timestamp('created_at');

            $table->index(['viewable_type', 'viewable_id', 'created_at'], 'media_view_events_aggregate_idx');
            $table->index('created_at', 'media_view_events_pruning_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_view_events');
    }
};
