<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('episode_watch_progress', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained('users')
                ->cascadeOnDelete();
            $table->foreignId('episode_id')
                ->constrained('media_episodes')
                ->cascadeOnDelete();
            $table->unsignedInteger('seconds')->default(0);
            $table->boolean('completed')->default(false);
            $table->timestamps();

            // One progress record per user per episode
            $table->unique(['user_id', 'episode_id']);

            // For continue-watching query: filter by user, order by updated_at
            $table->index(['user_id', 'completed', 'updated_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('episode_watch_progress');
    }
};
