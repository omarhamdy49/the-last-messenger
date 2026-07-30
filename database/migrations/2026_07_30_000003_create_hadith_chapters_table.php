<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_chapters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->restrictOnDelete();

            $table->string('chapter_number', 20);
            $table->unsignedInteger('sort_order')->default(0);

            $table->json('title');

            // sunnah.com renders a preamble before and a closing passage after a chapter's hadiths
            // (arabicIntro/englishIntro, arabicEnding/englishEnding). Both can be long.
            $table->json('intro')->nullable();
            $table->json('ending')->nullable();

            $table->boolean('is_published')->default(false);
            $table->timestamps();

            $table->unique(['book_id', 'chapter_number']);
            $table->index(['book_id', 'sort_order'], 'idx_hadith_chapters_reading_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_chapters');
    }
};
