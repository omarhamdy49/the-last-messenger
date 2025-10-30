<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('hadith', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('hadith_books')->onDelete('cascade');
            $table->foreignId('chapter_id')->nullable()->constrained('hadith_chapters')->onDelete('set null');
            $table->integer('hadith_number');
            $table->text('arabic_text');
            $table->text('narrator_chain')->nullable(); // Complete narrator chain as text
            $table->json('translations')->nullable(); // {"en": "...", "fr": "...", "tr": "...", "ur": "..."}
            $table->enum('grade', ['sahih', 'hasan', 'daif', 'mawdu', 'unknown'])->default('unknown');
            $table->string('reference_url')->nullable(); // Link to external source (e.g., sunnah.com)
            $table->unsignedBigInteger('views_count')->default(0);
            $table->timestamps();

            // Indexes
            $table->index('book_id');
            $table->index('chapter_id');
            $table->index(['book_id', 'hadith_number']);
            $table->index('grade');
            $table->index('views_count');
        });

        // Add full-text index for search (MySQL only)
        if (config('database.default') === 'mysql') {
            // Full-text index on Arabic text
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_arabic_text (arabic_text)');

            // Add generated column for English translation search
            DB::statement("ALTER TABLE hadith ADD COLUMN translation_en_searchable TEXT GENERATED ALWAYS AS (JSON_UNQUOTE(JSON_EXTRACT(translations, '$.en'))) STORED");

            // Full-text index on English translation
            DB::statement('ALTER TABLE hadith ADD FULLTEXT fulltext_translation_en (translation_en_searchable)');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith');
    }
};
