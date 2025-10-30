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
        // Drop old table structure
        Schema::dropIfExists('quran_tafseer');

        // Create new multi-language JSON-based table
        Schema::create('quran_tafseer', function (Blueprint $table) {
            $table->id();
            $table->string('verse_reference'); // Format: "2:255" (surah:ayah)
            $table->unsignedBigInteger('surah_id')->nullable(); // Cache for queries
            $table->string('ayah_number', 10)->nullable(); // Cache for queries
            $table->enum('source', [
                'ibn_kathir',
                'jalalayn',
                'sadi',
                'qurtubi',
                'tabari',
                'baghawi',
                'muyassar',
                'ashhari',
                'qasimi',
            ])->index();
            $table->json('text'); // Multi-language: {ar, en, fr, tr, ur}
            $table->string('author')->nullable();
            $table->json('footnotes')->nullable(); // Array of footnotes
            $table->enum('status', ['draft', 'published'])->default('draft')->index();
            $table->timestamps();
            $table->softDeletes();

            // Indexes
            $table->unique(['verse_reference', 'source'], 'unique_verse_source');
            $table->index('surah_id');
            $table->index('verse_reference');

            // Foreign key
            $table->foreign('surah_id')
                ->references('id')
                ->on('quran_surahs')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_tafseer');

        // Restore old table structure for rollback
        Schema::create('quran_tafseer', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->onDelete('cascade');
            $table->string('tafseer_name');
            $table->string('language', 2);
            $table->text('text');
            $table->text('footnotes')->nullable();
            $table->timestamps();

            $table->index('verse_id');
            $table->index('tafseer_name');
            $table->index('language');
            $table->index(['verse_id', 'tafseer_name', 'language']);
        });
    }
};
