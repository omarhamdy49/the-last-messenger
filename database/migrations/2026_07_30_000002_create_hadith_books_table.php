<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();

            // String, not numeric: carries "35b" and "introduction" directly. sunnah.com encodes
            // these as decimal(3,1) plus magic ids (-1 = introduction, -35 = "35b").
            $table->string('book_number', 20);
            $table->unsignedInteger('sort_order')->default(0);

            $table->json('title');
            $table->json('intro')->nullable();

            $table->string('first_hadith_number', 50)->nullable();
            $table->string('last_hadith_number', 50)->nullable();
            $table->unsignedInteger('hadith_count')->default(0);

            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'book_number']);
            $table->index(['collection_id', 'sort_order'], 'idx_hadith_books_reading_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_books');
    }
};
