<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('media_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')
                ->constrained('media_programs')
                ->cascadeOnDelete();
            $table->unsignedSmallInteger('season_number')->default(1);
            $table->unsignedSmallInteger('episode_number');
            $table->string('slug')->unique();
            $table->string('video_url', 500)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->unsignedInteger('views_count')->default(0)->index();
            $table->boolean('is_featured')->default(false);
            $table->timestamps();

            // Enforce one episode per season+number per program
            $table->unique(['program_id', 'season_number', 'episode_number']);

            $table->index(['program_id', 'season_number', 'episode_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('media_episodes');
    }
};
