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
        Schema::create('hadith_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_id')->constrained('hadith_hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('language_code', 10)->comment('Language code (ar, en, tr, fr, ur)');
            $table->longText('text')->comment('Hadith text');
            $table->text('notes')->nullable()->comment('Translation notes');
            $table->timestamp('created_at')->useCurrent();
            $table->timestamp('updated_at')->nullable();

            // Indexes
            $table->index('hadith_id');
            $table->index('language_code');
            $table->unique(['hadith_id', 'language_code']);
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_translations ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index for search
        DB::statement('ALTER TABLE hadith_translations ADD FULLTEXT INDEX ft_hadith_translations_text (text)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_translations');
    }
};
