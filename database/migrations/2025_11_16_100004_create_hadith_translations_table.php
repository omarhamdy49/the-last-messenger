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
            $table->foreignId('hadith_id')->constrained('hadiths')->onDelete('cascade')->comment('Reference to hadith');
            $table->string('language_code', 10)->comment('Language code (e.g., ar, en, ur, fr, tr)');
            $table->text('matn')->comment('Text of the hadith (matn)');
            $table->string('translator_name', 255)->nullable()->comment('Translator name (e.g., Muhammad Muhsin Khan)');
            $table->text('notes')->nullable()->comment('Translation notes');
            $table->timestamp('created_at')->useCurrent();

            // Indexes
            $table->index('language_code', 'idx_ht_lang');
        });

        // Set charset and collation
        DB::statement('ALTER TABLE hadith_translations ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_520_ci');

        // Add FULLTEXT index
        DB::statement('ALTER TABLE hadith_translations ADD FULLTEXT INDEX ft_ht_matn (matn)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_translations');
    }
};
