<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->onDelete('cascade')->comment('Reference to book');
            $table->integer('chapter_number')->nullable()->comment('Chapter number');
            $table->json('name')->comment('Chapter name (multilingual JSON)');
            $table->json('description')->nullable()->comment('Chapter description (multilingual JSON)');
            $table->integer('display_order')->nullable()->comment('Display order');
            $table->timestamps();

            // Indexes
            $table->index('book_id');
            $table->index('chapter_number');
            $table->index('display_order');
        });

        if (config('database.default') === 'mysql') {
            // Set charset and collation
            DB::statement('ALTER TABLE hadith_chapters ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_chapters');
    }
};
