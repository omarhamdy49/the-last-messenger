<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_texts', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->cascadeOnDelete();
            $table->enum('locale', ['ar', 'en', 'fr', 'tr', 'ur']);

            $table->longText('body');

            // Diacritics stripped, alef/hamza/ya folded. Arabic FULLTEXT matching runs against this
            // column, never `body` — searching diacritised text finds almost nothing.
            $table->longText('body_normalized')->nullable();

            $table->text('sanad')->nullable();
            $table->longText('matn')->nullable();

            // sunnah.com stores a per-language bab name on the hadith row itself, separately from
            // ChapterData, and the two legitimately differ. Kept as an override.
            $table->string('chapter_title', 1000)->nullable();

            $table->string('translator')->nullable();
            $table->string('source_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['hadith_id', 'locale']);
        });

        // One composite FULLTEXT index serves both scripts: Arabic matches via body_normalized,
        // Latin locales via body.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE hadith_texts ADD FULLTEXT ft_hadith_texts (`body`, `body_normalized`)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_texts');
    }
};
