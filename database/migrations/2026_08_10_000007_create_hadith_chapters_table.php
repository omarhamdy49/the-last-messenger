<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §7.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();

            // NOT unique, deliberately: real books repeat chapter numbers.
            $table->string('chapter_number', 50);

            $table->string('title_ar', 500);
            $table->string('title_en', 500)->nullable();
            $table->json('intro')->nullable();
            $table->json('ending')->nullable();

            $table->unsignedInteger('sort_order');
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'sort_order']);
            // Composite-FK parent key for `hadiths`, not a query index.
            $table->unique(['id', 'book_id']);

            // Chapter browsing is by book ordered on sort_order. There is
            // deliberately no (book_id, chapter_number) index — no shipped
            // query uses it, and constitution II forbids speculative indexes.
            $table->index(['book_id', 'is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_chapters');
    }
};
