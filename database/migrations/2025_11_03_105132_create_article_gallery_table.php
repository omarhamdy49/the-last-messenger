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
        Schema::create('article_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')
                ->constrained('articles')
                ->onDelete('cascade'); // Delete gallery images when article is deleted
            $table->string('image_path'); // Path to the image file
            $table->json('caption')->nullable(); // Multi-language caption {ar, en, fr, tr, ur}
            $table->json('alt_text')->nullable(); // Multi-language alt text for accessibility
            $table->integer('order')->default(0); // Display order (0 = first)
            $table->timestamps();

            // Indexes
            $table->index('article_id');
            $table->index('order');
            $table->index(['article_id', 'order']); // Composite index for efficient ordering queries
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('article_gallery');
    }
};
