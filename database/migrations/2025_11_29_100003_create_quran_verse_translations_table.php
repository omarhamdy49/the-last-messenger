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
        Schema::create('quran_verse_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->string('lang', 5); // ar, en, fr, tr, ur
            $table->text('text'); // Translation text with tashkeel
            $table->text('text_notashkeel')->nullable(); // Text without tashkeel (for search)

            $table->timestamps();

            // Indexes
            $table->index('verse_id');
            $table->index('lang');
            $table->unique(['verse_id', 'lang']);

            // Full-text index for search (MySQL only)
            if (config('database.default') === 'mysql') {
                $table->fullText(['text', 'text_notashkeel']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_verse_translations');
    }
};
