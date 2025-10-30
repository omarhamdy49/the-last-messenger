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
        Schema::create('media_episodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('media_programs')->onDelete('cascade');
            $table->string('slug')->unique();
            $table->integer('episode_number');
            $table->json('title'); // Multi-language
            $table->json('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('video_url'); // YouTube, Vimeo, or direct URL
            $table->integer('duration_minutes')->default(0);
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            // Indexes
            $table->index('program_id');
            $table->index(['program_id', 'episode_number']);
            $table->index('published_at');
            $table->index('views_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_episodes');
    }
};
