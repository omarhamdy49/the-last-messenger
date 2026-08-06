<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §6.
 *
 * No `is_synthetic`: a nullable `hadiths.book_id` replaces the old hidden
 * synthetic books, which means the bookless browse path is rewritten rather
 * than ported.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();

            // STRING, not an integer: real source values include '35b',
            // 'introduction' and 'letter'.
            $table->string('book_number', 50);

            $table->string('title_ar', 500);
            $table->string('title_en', 500)->nullable();
            $table->json('intro')->nullable();

            // Renamed from `from`/`to`, which are reserved words in MySQL 8.
            $table->string('first_hadith_number', 50)->nullable();
            $table->string('last_hadith_number', 50)->nullable();

            $table->unsignedInteger('sort_order');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'book_number']);
            $table->unique(['collection_id', 'sort_order']);
            // Parent key for the composite FK on `hadiths`. MySQL requires a
            // matching unique key on the referenced side; this is not a query
            // index and no read path uses it.
            $table->unique(['id', 'collection_id']);

            $table->index(['collection_id', 'is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_books');
    }
};
