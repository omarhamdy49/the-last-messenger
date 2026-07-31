<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * data-model.md §2.
 *
 * `book_number` is a string because the source carries `35b`, `introduction`
 * and `letter`. Real ordering lives in `sort_order`.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hadith_books')) {
            return;
        }

        Schema::create('hadith_books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('collection_id')->constrained('hadith_collections')->restrictOnDelete();
            $table->string('book_number', 20);
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('title');
            $table->json('intro')->nullable();
            $table->string('first_hadith_number', 50)->nullable();
            $table->string('last_hadith_number', 50)->nullable();
            // Hidden placeholder for bookless collections (the three forties).
            $table->boolean('is_synthetic')->default(false);
            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['collection_id', 'book_number']);
            $table->index(['collection_id', 'sort_order']);
        });

        // Parent key for the composite foreign key on hadith_hadiths
        // (book_id, collection_id). MySQL requires a UNIQUE index on exactly
        // these columns before it will accept the child constraint; SQLite has
        // no such requirement but accepts the index harmlessly.
        Schema::table('hadith_books', function (Blueprint $table): void {
            $table->unique(['id', 'collection_id'], 'hadith_books_id_collection_id_unique');
        });
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            Schema::table('hadith_books', function (Blueprint $table): void {
                $table->dropUnique('hadith_books_id_collection_id_unique');
            });
        }

        Schema::dropIfExists('hadith_books');
    }
};
