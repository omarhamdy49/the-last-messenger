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
        Schema::create('articles_gallery', function (Blueprint $table) {
            $table->id();
            $table->foreignId('article_id')->constrained('articles')->onDelete('cascade')->comment('Article reference');
            $table->string('image')->comment('Gallery image path');
            $table->json('caption')->nullable()->comment('Multi-language caption: {ar, en, fr, tr, ur}');
            $table->integer('order')->default(0)->comment('Display order');
            $table->timestamps();

            // Indexes
            $table->index('article_id');
            $table->index('order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles_gallery');
    }
};
