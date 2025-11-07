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
        Schema::create('articles', function (Blueprint $table) {
            $table->id();
            $table->string('thumb')->nullable()->comment('Thumbnail image path');
            $table->foreignId('writer_id')->nullable()->constrained('users')->onDelete('set null')->comment('Article writer/author');
            $table->integer('read_time')->default(0)->comment('Reading time in minutes');
            $table->bigInteger('views')->default(0)->comment('Total views count');
            $table->boolean('is_featured')->default(false)->comment('Featured article flag');
            $table->boolean('is_pinned')->default(false)->comment('Pinned article flag');
            $table->foreignId('category_id')->nullable()->constrained('categories')->onDelete('set null')->comment('Primary category');
            $table->string('hijri_date')->nullable()->comment('Hijri date for article');
            $table->json('statistics')->nullable()->comment('Article statistics: shares, likes, bookmarks, etc.');
            $table->json('references')->nullable()->comment('Article references: [{title, url, type}, ...]');
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->index('writer_id');
            $table->index('category_id');
            $table->index('is_featured');
            $table->index('is_pinned');
            $table->index('created_at');
            $table->index('views');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('articles');
    }
};
