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
        Schema::create('hadith_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->onDelete('cascade');
            $table->integer('chapter_number');
            $table->json('name'); // Multi-language
            $table->json('description')->nullable();
            $table->integer('display_order')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('book_id');
            $table->index(['book_id', 'chapter_number']);
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_chapters');
    }
};
