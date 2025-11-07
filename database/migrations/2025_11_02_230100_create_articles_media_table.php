<?php

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
        Schema::create('articles_media', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade')->comment('Article reference');
            $table->foreignId('media_id')->constrained('media')->onDelete('cascade')->comment('Media reference (Spatie Media Library)');
            $table->timestamps();

            // Indexes
            $table->index('article_id');
            $table->index('media_id');
            $table->unique(['article_id', 'media_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles_media');
    }
};
