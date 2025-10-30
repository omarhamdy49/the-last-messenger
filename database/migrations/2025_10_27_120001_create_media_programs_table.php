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
        Schema::create('media_programs', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->foreignId('category_id')->constrained('media_categories')->onDelete('cascade');
            $table->json('title'); // Multi-language
            $table->json('description')->nullable();
            $table->string('thumbnail')->nullable();
            $table->string('banner_image')->nullable();
            $table->integer('total_episodes')->default(0);
            $table->unsignedBigInteger('total_views')->default(0);
            $table->enum('status', ['ongoing', 'completed', 'paused'])->default('ongoing');
            $table->timestamps();

            // Indexes
            $table->index('slug');
            $table->index('category_id');
            $table->index('status');
            $table->index('total_views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('media_programs');
    }
};
